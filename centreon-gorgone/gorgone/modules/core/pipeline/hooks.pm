#
# Copyright 2019 - 2024 Centreon (http://www.centreon.com/)
#
# Centreon is a full-fledged industry-strength solution that meets
# the needs in IT infrastructure and application monitoring for
# service performance.
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
#

package gorgone::modules::core::pipeline::hooks;

use warnings;
use strict;
use gorgone::class::core;
use gorgone::modules::core::pipeline::class;
use gorgone::standard::constants qw(:all);

use constant NAMESPACE => 'core';
use constant NAME => 'pipeline';
use constant EVENTS => [
    { event => 'PIPELINEREADY' },
    { event => 'PIPELINELISTENER' },
    { event => 'ADDPIPELINE', uri => '/definitions', method => 'POST' },
];

my $config_core;
my $config;
my $pipeline = {};
my $stop = 0;

sub register {
    my (%options) = @_;

    $config = $options{config};
    $config_core = $options{config_core};
    $config->{purge_sessions_time} =
        defined($config->{purge_sessions_time}) && $config->{purge_sessions_time} =~ /(\d+)/ ?
        $1 :
        3600
    ;
    $config->{purge_history_time} =
        defined($config->{purge_history_time}) && $config->{purge_history_time} =~ /(\d+)/ ?
        $1 :
        604800
    ;
    return (1, NAMESPACE, NAME, EVENTS);
}

sub init {
    my (%options) = @_;

    create_child(logger => $options{logger});
}

sub routing {
    my (%options) = @_;

    if ($options{action} eq 'PIPELINEREADY') {
        $pipeline->{ready} = 1;
        return undef;
    }

    if (gorgone::class::core::waiting_ready(ready => \$pipeline->{ready}) == 0) {
        gorgone::standard::library::add_history({
            dbh => $options{dbh},
            code => GORGONE_ACTION_FINISH_KO,
            token => $options{token},
            data => { message => 'gorgone-pipeline: still no ready' },
            json_encode => 1
        });
        return undef;
    }

    $options{gorgone}->send_internal_message(
        identity => 'gorgone-pipeline',
        action => $options{action},
        raw_data_ref => $options{frame}->getRawData(),
        token => $options{token}
    );
}

sub gently {
    my (%options) = @_;

    $stop = 1;
    if (defined($pipeline->{running}) && $pipeline->{running} == 1) {
        $options{logger}->writeLogDebug("[pipeline] Send TERM signal $pipeline->{pid}");
        CORE::kill('TERM', $pipeline->{pid});
    }
}

sub kill {
    my (%options) = @_;

    if ($pipeline->{running} == 1) {
        $options{logger}->writeLogDebug('[pipeline] Send KILL signal for subprocess');
        CORE::kill('KILL', $pipeline->{pid});
    }
}

sub kill_internal {
    my (%options) = @_;

}

sub check {
    my (%options) = @_;

    my $count = 0;
    foreach my $pid (keys %{$options{dead_childs}}) {
        # Not me
        next if (!defined($pipeline->{pid}) || $pipeline->{pid} != $pid);

        $pipeline = {};
        delete $options{dead_childs}->{$pid};
        if ($stop == 0) {
            create_child(logger => $options{logger});
        }
    }

    $count++ if (defined($pipeline->{running}) && $pipeline->{running} == 1);

    return $count;
}

sub broadcast {
    my (%options) = @_;

    routing(%options);
}

# Specific functions
sub create_child {
    my (%options) = @_;

    $options{logger}->writeLogInfo("[pipeline] Create module 'pipeline' process");
    my $child_pid = fork();
    if ($child_pid == 0) {
        $0 = 'gorgone-pipeline';
        my $module = gorgone::modules::core::pipeline::class->new(
            logger => $options{logger},
            module_id => NAME,
            config_core => $config_core,
            config => $config,
        );
        $module->run();
        exit(0);
    }
    $options{logger}->writeLogDebug("[pipeline] PID $child_pid (gorgone-pipeline)");
    $pipeline = { pid => $child_pid, ready => 0, running => 1 };
}

1;
