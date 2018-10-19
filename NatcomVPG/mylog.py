import logging

LOG_FILENAME = '/u/debit/logs/example.log'

def slog(msg, loglevel=logging.DEBUG):
    logging.basicConfig(filename=LOG_FILENAME,level=loglevel)
    logging.debug(msg)


slog('This message should go to the log file - Bit %s of type %s with value = %s' % ('arg1','arg2','arg3'))
