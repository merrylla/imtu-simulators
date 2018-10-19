import syslog

syslog.openlog('mysyslogprocess',logoption=syslog.LOG_PID)

error=syslog.syslog('Processing started')
if error:
    syslog.syslog(syslog.LOG_ERR, 'Processing started')
