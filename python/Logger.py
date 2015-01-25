# -*- coding: utf-8 -*-

import logging
import logging.handlers
import time
import sys
import os
import multiprocessing


class ParentCentralLogger(multiprocessing.Process):
    """
    Base class for centralized logger.
    """

    __namePrefix__ = ''
    __logLevel__ = logging.DEBUG
    handler = None

    def __init__(self, name, queue, settings):
        """
        Constructor.

        @type  name: str / float / int
        @param name: unique name for logger instance in memory
        @type  queue: multiprocessing.JoinableQueue
        @param queue: queue for logging
        @type  settings: dict
        @param settings: main settings
        """
        multiprocessing.Process.__init__(self)
        self.name = '{:s}{:d}'.format(self.__namePrefix__, name)
        self.queue = queue
        self.settings = settings
        self.logger = self._create_logger()

    def _set_handler(self):
        """
        Sets handler for logger.
        """
        self.handler = logging.StreamHandler(stream=sys.stdout)

    def _get_format(self):
        """
        Gets formatter for logger.

        @rtype:  logging.Formatter
        @return: formatter for logger
        """
        format_string = self.settings.get('format')
        datetime_format_string = self.settings.get('datetime_format')
        # set format of messages in log and apply it to the created handler
        fmt = logging.Formatter(fmt=format_string,
                                datefmt=datetime_format_string)

        timezone_string = self.settings.get('timezone').strip().lower()
        if timezone_string == 'utc':
            fmt.converter = time.gmtime
        elif timezone_string == 'local':
            fmt.converter = time.localtime
        else:
            fmt.converter = time.localtime
        return fmt

    def _create_logger(self):
        """
        Creates logger instance.

        @rtype:  logging.getLogger
        @return: logger instance
        """
        logger_instance = logging.getLogger(self.name)
        self._set_handler()
        log_format = self._get_format()
        self.handler.setFormatter(log_format)
        logger_instance.addHandler(self.handler)
        logger_instance.setLevel(self.__logLevel__)
        return logger_instance

    """
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
    """

    def run(self):
        """
        Starts monitoring for logging queue.
        """
        while True:
            msg = self.queue.get()
            if msg is None:  # None means stopping of queue monitoring
                break
            else:
                if self.settings.get('enabled'):
                    self.logger.log(self.__logLevel__, msg)

    def stop(self):
        """
        Stops monitoring for logging queue.
        """
        self.queue.put(None)  # "poison pill" for queue ending
        self.join()


class ConsoleCentralLogger(ParentCentralLogger):
    """
    Class for centralized logger to console stream.
    """

    __namePrefix__ = 'console_logger'
    __logLevel__ = logging.DEBUG

    def _set_handler(self):
        """
        Sets handler for logger.
        """
        self.handler = logging.StreamHandler(stream=sys.stdout)


class ErrorCentralLogger(ParentCentralLogger):
    """
    Class for centralized logger to error log file (with rotation).
    """

    __namePrefix__ = 'error_logger_'
    __logLevel__ = logging.ERROR

    def _set_handler(self):
        """
        Sets handler for logger.
        """
        path = self.settings.get('directory_path')
        # ensure that logs directory path exists
        if not os.path.exists(path):
            os.makedirs(path)
        filename = '{:s}{:s}'.format(path,
                                     self.settings.get('filename'))

        self.handler = logging.handlers.RotatingFileHandler(
            filename=filename,
            mode='a',
            maxBytes=int(self.settings.get('max_bytes')),
            backupCount=int(self.settings.get('backups_count')))


class DebugCentralLogger(ErrorCentralLogger):
    """
    Class for centralized logger to debug log file (with rotation).
    """

    __namePrefix__ = 'debug_logger_'
    __logLevel__ = logging.DEBUG
