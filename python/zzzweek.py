from logging.handlers import RotatingFileHandler
import multiprocessing
import threading
import logging
import os
import sys
import traceback
import time


class MultiProcessingLogHandler(logging.Handler):

    def __init__(self, name, mode, maxsize, rotate):
        logging.Handler.__init__(self)

        self._handler = RotatingFileHandler(filename=name,
                                            mode=mode,
                                            maxBytes=maxsize,
                                            backupCount=rotate)
        self.queue = multiprocessing.Queue(-1)

        t = threading.Thread(target=self.receive)
        t.daemon = True
        t.start()

    def setFormatter(self, fmt):
        logging.Handler.setFormatter(self, fmt)
        self._handler.setFormatter(fmt)

    def receive(self):
        while True:
            try:
                record = self.queue.get()
                self._handler.emit(record)
            except (KeyboardInterrupt, SystemExit):
                raise
            except EOFError:
                break
            except:
                traceback.print_exc(file=sys.stderr)

    def send(self, s):
        self.queue.put_nowait(s)

    def _format_record(self, record):
        # ensure that exc_info and args have been stringified.
        # Removes any chance of un-pickle-able things inside and possibly
        # reduces message size sent over the pipe
        if record.args:
            record.msg = record.msg % record.args
            record.args = None
        if record.exc_info:
            dummy = self.format(record)
            record.exc_info = None
        return record

    def emit(self, record):
        try:
            s = self._format_record(record)
            self.send(s)
        except (KeyboardInterrupt, SystemExit):
            raise
        except:
            self.handleError(record)

    def close(self):
        self._handler.close()
        logging.Handler.close(self)


def task(number):
    time.sleep(0.05)
    logging.error('Hi from process {}; number: {}'.format(os.getpid(), number))


h = MultiProcessingLogHandler('zzzweek.log', 'a', 1024, 5)
f = logging.Formatter(
    '%(asctime)s '
    '%(processName)-10s '
    '%(name)s '
    '%(levelname)-8s ::    '
    '%(message)s')
h.setFormatter(f)
logging.getLogger().addHandler(h)

pool = multiprocessing.Pool()
pool.map(task, range(8))
