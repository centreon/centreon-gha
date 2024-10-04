-- 
-- Contenu de la table `nagios_macro`
-- 

INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 1, '$HOSTNAME$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 2, '$_HOSTSNMPCOMMUNITY$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 3, '$_HOSTSNMPVERSION$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 5, '$ARGn$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 6, '$HOSTALIAS$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 7, '$HOSTADDRESS$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 8, '$HOSTSTATE$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 9, '$HOSTSTATEID$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 10, '$HOSTSTATETYPE$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 11, '$HOSTATTEMPT$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 12, '$HOSTLATENCY$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 13, '$HOSTEXECUTIONTIME$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 14, '$HOSTDURATION$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 15, '$HOSTDURATIONSEC$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 16, '$HOSTDOWNTIME$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 17, '$HOSTPERCENTCHANGE$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 18, '$HOSTGROUPNAME$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 19, '$HOSTGROUPALIAS$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 20, '$LASTHOSTCHECK$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 21, '$LASTHOSTSTATECHANGE$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 22, '$LASTHOSTUP$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 23, '$LASTHOSTDOWN$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 24, '$LASTHOSTUNREACHABLE$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 25, '$HOSTOUTPUT$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 26, '$HOSTPERFDATA$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 27, '$HOSTCHECKCOMMAND$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 30, '$HOSTACTIONURL$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 31, '$HOSTNOTESURL$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 32, '$HOSTNOTES$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 33, '$SERVICEDESC$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 34, '$SERVICESTATE$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 35, '$SERVICESTATEID$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 36, '$SERVICESTATETYPE$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 37, '$SERVICEATTEMPT$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 38, '$SERVICELATENCY$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 39, '$SERVICEEXECUTIONTIME$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 40, '$SERVICEDURATION$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 41, '$SERVICEDURATIONSEC$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 42, '$SERVICEDOWNTIME$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 43, '$SERVICEPERCENTCHANGE$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 44, '$SERVICEGROUPNAME$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 45, '$SERVICEGROUPALIAS$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 46, '$LASTSERVICECHECK$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 47, '$LASTSERVICESTATECHANGE$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 48, '$LASTSERVICEOK$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 49, '$LASTSERVICEWARNING$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 50, '$LASTSERVICEUNKNOWN$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 51, '$LASTSERVICECRITICAL$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 52, '$SERVICEOUTPUT$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 53, '$LONGSERVICEOUTPUT$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 54, '$SERVICEPERFDATA$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 55, '$SERVICECHECKCOMMAND$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 58, '$SERVICEACTIONURL$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 59, '$SERVICENOTESURL$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 60, '$SERVICENOTES$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 61, '$TOTALHOSTSUP$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 62, '$TOTALHOSTSDOWN$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 63, '$TOTALHOSTSUNREACHABLE$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 64, '$TOTALHOSTSDOWNUNHANDLED$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 65, '$TOTALHOSTSUNREACHABLEUNHANDLED$ 	');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 66, '$TOTALHOSTPROBLEMS$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 67, '$TOTALHOSTPROBLEMSUNHANDLED$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 68, '$TOTALSERVICESOK$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 69, '$TOTALSERVICESWARNING$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 70, '$TOTALSERVICESCRITICAL$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 71, '$TOTALSERVICESUNKNOWN$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 72, '$TOTALSERVICESWARNINGUNHANDLED$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 73, '$TOTALSERVICESCRITICALUNHANDLED$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 74, '$TOTALSERVICESUNKNOWNUNHANDLED$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 75, '$TOTALSERVICEPROBLEMS$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 76, '$TOTALSERVICEPROBLEMSUNHANDLED$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 77, '$NOTIFICATIONTYPE$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 78, '$NOTIFICATIONNUMBER$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 79, '$CONTACTNAME$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 80, '$CONTACTALIAS$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 81, '$CONTACTEMAIL$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 82, '$CONTACTPAGER$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 83, '$CONTACTADDRESSn$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 84, '$LONGDATETIME$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 85, '$SHORTDATETIME$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 86, '$DATE$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 87, '$TIME$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 88, '$TIMET$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 89, '$MAINCONFIGFILE$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 90, '$STATUSDATAFILE$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 91, '$COMMENTDATAFILE$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 92, '$DOWNTIMEDATAFILE$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 93, '$RETENTIONDATAFILE$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 94, '$OBJECTCACHEFILE$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 95, '$TEMPFILE$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 96, '$LOGFILE$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 97, '$RESOURCEFILE$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 98, '$COMMANDFILE$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 99, '$HOSTPERFDATAFILE$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 100, '$SERVICEPERFDATAFILE$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 101, '$PROCESSSTARTTIME$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 102, '$ADMINEMAIL$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 103, '$ADMINPAGER$');
INSERT INTO `nagios_macro` (`macro_id`, `macro_name`) VALUES ( 104, '$USERn$');
INSERT INTO `nagios_macro` (`macro_name`) VALUES ('$HOSTID$');
INSERT INTO `nagios_macro` (`macro_name`) VALUES ('$SERVICEID$');
INSERT INTO `nagios_macro` (`macro_name`) VALUES ('$_HOSTCRITICALITY_LEVEL$');
INSERT INTO `nagios_macro` (`macro_name`) VALUES ('$_SERVICECRITICALITY_LEVEL$');
INSERT INTO `nagios_macro` (`macro_name`) VALUES ('$_HOSTCRITICALITY_ID$');
INSERT INTO `nagios_macro` (`macro_name`) VALUES ('$_SERVICECRITICALITY_ID$');
INSERT INTO `nagios_macro` (`macro_name`) VALUES ('$HOSTTIMEZONE$');
INSERT INTO `nagios_macro` (`macro_name`) VALUES ('$NOTIFICATIONAUTHOR$');
INSERT INTO `nagios_macro` (`macro_name`) VALUES ('$NOTIFICATIONAUTHORNAME$');
INSERT INTO `nagios_macro` (`macro_name`) VALUES ('$NOTIFICATIONAUTHORALIAS$');
INSERT INTO `nagios_macro` (`macro_name`) VALUES ('$NOTIFICATIONCOMMENT$');
