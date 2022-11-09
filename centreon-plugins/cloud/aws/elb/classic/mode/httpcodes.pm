#
# Copyright 2022 Centreon (http://www.centreon.com/)
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

package cloud::aws::elb::classic::mode::httpcodes;

use base qw(centreon::plugins::templates::counter);

use strict;
use warnings;

my %metrics_mapping = (
    'HTTPCode_Backend_2XX' => { # Minimum, Maximum, and Average all return 1.
        'output' => 'HTTP 2XXs',
        'label' => 'httpcode-backend-2xx',
        'nlabel' => 'elb.httpcode.backend.2xx.count',
    },
    'HTTPCode_Backend_3XX' => { # Minimum, Maximum, and Average all return 1.
        'output' => 'HTTP 3XXs',
        'label' => 'httpcode-backend-3xx',
        'nlabel' => 'elb.httpcode.backend.3xx.count',
    },
    'HTTPCode_Backend_4XX' => { # Minimum, Maximum, and Average all return 1.
        'output' => 'HTTP 4XXs',
        'label' => 'httpcode-backend-4xx',
        'nlabel' => 'elb.httpcode.backend.4xx.count',
    },
    'HTTPCode_Backend_5XX' => { # Minimum, Maximum, and Average all return 1.
        'output' => 'HTTP 5XXs',
        'label' => 'httpcode-backend-5xx',
        'nlabel' => 'elb.httpcode.backend.5xx.count',
    },
    'HTTPCode_ELB_4XX' => { # Minimum, Maximum, and Average all return 1.
        'output' => 'ELB HTTP 4XXs',
        'label' => 'httpcode-elb-4xx',
        'nlabel' => 'elb.httpcode.elb.4xx.count',
    },
    'HTTPCode_ELB_5XX' => { # Minimum, Maximum, and Average all return 1.
        'output' => 'ELB HTTP 5XXs',
        'label' => 'httpcode-elb-5xx',
        'nlabel' => 'elb.httpcode.elb.5xx.count',
    },
    'BackendConnectionErrors' => {
        'output' => 'Backend Connection Errors',
        'label' => 'backendconnectionerrors',
        'nlabel' => 'elb.backendconnectionerrors.count',
    },
);

my %map_type = (
    "loadbalancer"      => "LoadBalancerName",
    "availabilityzone"  => "AvailabilityZone",
);

sub prefix_metric_output {
    my ($self, %options) = @_;

    my $availability_zone = "";
    if (defined($options{instance_value}->{availability_zone}) && $options{instance_value}->{availability_zone} ne '') {
        $availability_zone = "[$options{instance_value}->{availability_zone}] ";
    }
    
    return ucfirst($self->{option_results}->{type}) . " '" . $options{instance_value}->{display} . "' " . $availability_zone;
}

sub prefix_statistics_output {
    my ($self, %options) = @_;
    
    return "Statistic '" . $options{instance_value}->{display} . "' Metrics ";
}

sub long_output {
    my ($self, %options) = @_;

    my $availability_zone = "";
    if (defined($options{instance_value}->{availability_zone}) && $options{instance_value}->{availability_zone} ne '') {
        $availability_zone = "[$options{instance_value}->{availability_zone}] ";
    }

    return "Checking " . ucfirst($self->{option_results}->{type}) . " '" . $options{instance_value}->{display} . "' " . $availability_zone;
}

sub set_counters {
    my ($self, %options) = @_;
        
    $self->{maps_counters_type} = [
        { name => 'metrics', type => 3, cb_prefix_output => 'prefix_metric_output', cb_long_output => 'long_output',
          message_multiple => 'All elb metrics are ok', indent_long_output => '    ',
            group => [
                { name => 'statistics', display_long => 1, cb_prefix_output => 'prefix_statistics_output',
                  message_multiple => 'All metrics are ok', type => 1, skipped_code => { -10 => 1 } },
            ]
        }
    ];

    foreach my $metric (keys %metrics_mapping) {
        my $entry = {
            label => $metrics_mapping{$metric}->{label},
            nlabel => $metrics_mapping{$metric}->{nlabel},
            set => {
                key_values => [ { name => $metric }, { name => 'display' } ],
                output_template => $metrics_mapping{$metric}->{output} . ': %.2f',
                perfdatas => [
                    { value => $metric , template => '%.2f', label_extra_instance => 1 }
                ],
            }
        };
        push @{$self->{maps_counters}->{statistics}}, $entry;
    }
}

sub new {
    my ($class, %options) = @_;
    my $self = $class->SUPER::new(package => __PACKAGE__, %options, force_new_perfdata => 1);
    bless $self, $class;
    
    $options{options}->add_options(arguments => {
        "type:s"                => { name => 'type' },
        "name:s@"               => { name => 'name' },
        "availability-zone:s"   => { name => 'availability_zone' },
        "filter-metric:s"       => { name => 'filter_metric' },
    });
    
    return $self;
}

sub check_options {
    my ($self, %options) = @_;
    $self->SUPER::check_options(%options);

    if (!defined($self->{option_results}->{type}) || $self->{option_results}->{type} eq '') {
        $self->{output}->add_option_msg(short_msg => "Need to specify --type option.");
        $self->{output}->option_exit();
    }

    if ($self->{option_results}->{type} ne 'loadbalancer' && $self->{option_results}->{type} ne 'availabilityzone') {
        $self->{output}->add_option_msg(short_msg => "Instance type '" . $self->{option_results}->{type} . "' is not handled for this mode");
        $self->{output}->option_exit();
    }

    if ($self->{option_results}->{type} eq 'availabilityzone' && defined($self->{option_results}->{availability_zone})) {
        $self->{output}->add_option_msg(short_msg => "Can't specify --availability-zone option with availabilityzone instance's type");
        $self->{output}->option_exit();
    }

    if (!defined($self->{option_results}->{name}) || $self->{option_results}->{name} eq '') {
        $self->{output}->add_option_msg(short_msg => "Need to specify --name option.");
        $self->{output}->option_exit();
    }

    foreach my $instance (@{$self->{option_results}->{name}}) {
        if ($instance ne '') {
            push @{$self->{aws_instance}}, $instance;
        }
    }

    $self->{aws_timeframe} = defined($self->{option_results}->{timeframe}) ? $self->{option_results}->{timeframe} : 600;
    $self->{aws_period} = defined($self->{option_results}->{period}) ? $self->{option_results}->{period} : 60;

    $self->{aws_statistics} = ['Sum'];
    if (defined($self->{option_results}->{statistic})) {
        $self->{aws_statistics} = [];
        foreach my $stat (@{$self->{option_results}->{statistic}}) {
            if ($stat ne '') {
                push @{$self->{aws_statistics}}, ucfirst(lc($stat));
            }
        }
    }

    foreach my $metric (keys %metrics_mapping) {
        next if (defined($self->{option_results}->{filter_metric}) && $self->{option_results}->{filter_metric} ne ''
            && $metric !~ /$self->{option_results}->{filter_metric}/);

        push @{$self->{aws_metrics}}, $metric;
    }
}

sub manage_selection {
    my ($self, %options) = @_;

    my %metric_results;
    foreach my $instance (@{$self->{aws_instance}}) {
        push @{$self->{aws_dimensions}}, { Name => $map_type{$self->{option_results}->{type}}, Value => $instance };
        if (defined($self->{option_results}->{availability_zone}) && $self->{option_results}->{availability_zone} ne '') {
            push @{$self->{aws_dimensions}}, { Name => 'AvailabilityZone', Value => $self->{option_results}->{availability_zone} };
        }
        $metric_results{$instance} = $options{custom}->cloudwatch_get_metrics(
            namespace => 'AWS/ELB',
            dimensions => $self->{aws_dimensions},
            metrics => $self->{aws_metrics},
            statistics => $self->{aws_statistics},
            timeframe => $self->{aws_timeframe},
            period => $self->{aws_period},
        );
        
        foreach my $metric (@{$self->{aws_metrics}}) {
            foreach my $statistic (@{$self->{aws_statistics}}) {
                next if (!defined($metric_results{$instance}->{$metric}->{lc($statistic)}) && !defined($self->{option_results}->{zeroed}));

                $self->{metrics}->{$instance}->{display} = $instance;
                $self->{metrics}->{$instance}->{availability_zone} = $self->{option_results}->{availability_zone};
                $self->{metrics}->{$instance}->{statistics}->{lc($statistic)}->{display} = $statistic;
                $self->{metrics}->{$instance}->{statistics}->{lc($statistic)}->{$metric} = defined($metric_results{$instance}->{$metric}->{lc($statistic)}) ? $metric_results{$instance}->{$metric}->{lc($statistic)} : 0;
            }
        }
    }
    
    if (scalar(keys %{$self->{metrics}}) <= 0) {
        $self->{output}->add_option_msg(short_msg => 'No metrics. Check your options or use --zeroed option to set 0 on undefined values');
        $self->{output}->option_exit();
    }
}

1;

__END__

=head1 MODE

Check Classic ELB HTTP codes metrics.

Example: 
perl centreon_plugins.pl --plugin=cloud::aws::elb::classic::plugin --custommode=paws --mode=http-codes --region='eu-west-1'
--type='loadbalancer' --name='elb-www-fr' --critical-httpcode-backend-4xx='10' --verbose

See 'https://docs.aws.amazon.com/elasticloadbalancing/latest/classic/elb-cloudwatch-metrics.html' for more informations.

Default statistic: 'sum' / Most usefull statistics: 'sum'.

=over 8

=item B<--type>

Set the instance type (Required) (Can be: 'loadbalancer', 'availabilityzone').

=item B<--name>

Set the instance name (Required) (Can be multiple).

=item B<--availability-zone>

Add Availability Zone dimension (only with --type='loadbalancer').

=item B<--filter-metric>

Filter metrics (Can be: 'HTTPCode_Backend_2XX', 'HTTPCode_Backend_3XX', 'HTTPCode_Backend_4XX',
'HTTPCode_Backend_5XX', 'HTTPCode_ELB_4XX', 'HTTPCode_ELB_5XX', 'BackendConnectionErrors') 
(Can be a regexp).

=item B<--warning-*> B<--critical-*>

Thresholds warning (Can be: 'httpcode-backend-2xx', 'httpcode-backend-3xx',
'httpcode-backend-4xx', 'httpcode-backend-5xx', 'httpcode-elb-4xx',
'httpcode-elb-5xx', 'backendconnectionerrors')

=back

=cut
