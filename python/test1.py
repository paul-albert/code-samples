import multiprocessing
import logging
import logging.handlers
import time


class CustomizedDateTimeFormatter(logging.Formatter):
    """
    Additional class for customize date/time format in log
    """

    # set 'converter' attribute to use UTC instead of local timezone
    #converter = time.gmtime
    # set 'converter' attribute to use local timezone
    converter = time.localtime

    FORMAT_DEFAULT = '%Y-%m-%d %H:%M:%S'  # default format for date/time

    def formatTime(self, record, date_fmt=None):
        """
        Formats date/time for log.

        @type  record: LogRecord
        @param record: single record in log
        @type  date_fmt: str
        @param date_fmt: format for date/time (None by default)

        @rtype:  str
        @return: formatted string for date/time to log
        """
        ct = self.converter(record.created)
        if date_fmt:
            return time.strftime(date_fmt, ct)
        else:
            #try:
            #    milliseconds = int(record.msecs)
            #except AttributeError:
            #    milliseconds = 0
            milliseconds = 0
            if record:
                milliseconds = int(getattr(record, 'msecs', 0))
            return '{:s}.{:03d}'.format(time.strftime(self.FORMAT_DEFAULT, ct),
                                        milliseconds)


class CentralLogger(multiprocessing.Process):

    def __init__(self, name, queue, log_level=logging.DEBUG):
        multiprocessing.Process.__init__(self)
        self.queue = queue
        self.main_log_level = log_level
        self.logger = self.create_logger(name)
        self.logger.info("Started Central Logging process")
        #self.info("Started Central Logging process")

    def create_logger(self, name):
        logger_instance = logging.getLogger(name)
        handler = logging.handlers.RotatingFileHandler(
            filename='test1.log', mode='a', maxBytes=1024, backupCount=5)
        format_string = '%(asctime)s ::: ' \
                        '%(processName)-15s [%(process)s] ::: ' \
                        '%(name)s ::: ' \
                        '%(levelname)-8s ::: ' \
                        '%(message)s'
        # set format of messages in log and apply it to the created handler
        fmt = CustomizedDateTimeFormatter(fmt=format_string)
        handler.setFormatter(fmt)
        logger_instance.addHandler(handler)
        logger_instance.setLevel(self.main_log_level)
        return logger_instance

    def debug(self, msg, *args, **kwargs):
        self.logger.debug(msg, *args, **kwargs)

    def info(self, msg, *args, **kwargs):
        self.logger.info(msg, *args, **kwargs)

    def warning(self, msg, *args, **kwargs):
        self.logger.warning(msg, *args, **kwargs)

    warn = warning

    def error(self, msg, *args, **kwargs):
        self.logger.error(msg, *args, **kwargs)

    def exception(self, msg, *args, **kwargs):
        kwargs['exc_info'] = 1
        self.error(msg, *args, **kwargs)

    def critical(self, msg, *args, **kwargs):
        self.logger.critical(msg, *args, **kwargs)

    fatal = critical

    def run(self):
        while True:
            (log_level, msg) = self.queue.get()
            if log_level is None:
                self.info("Shutting down Central Logging process")
                break
            else:
                self.logger.log(log_level, msg)

    def stop(self):
        self.queue.put((None, ''))


def main():
    log_queue = multiprocessing.JoinableQueue()

    logger_process = CentralLogger('test1', log_queue,
                                   log_level=logging.DEBUG)
    logger_process.start()

    log_queue.put((logging.INFO,    'Begin'))
    log_queue.put((logging.DEBUG,   'Message #1'))
    log_queue.put((logging.DEBUG,   'Message #2'))
    log_queue.put((logging.DEBUG,   'Message #3'))
    log_queue.put((logging.ERROR,   'Some error'))
    log_queue.put((logging.WARNING, 'Some warning #1'))
    log_queue.put((logging.INFO,    'End'))

    #log_queue.put((None, ''))
    logger_process.stop()

if __name__ == '__main__':
    main()
