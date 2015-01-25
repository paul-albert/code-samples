#!/usr/bin/python
# -*- coding: utf-8 -*-

import sys
import logging
import logging.handlers
import os
#import threading
import multiprocessing


def my_print(obj, end='\n'):
    sys.stdout.write('{:s}{:s}'.format(str(obj), end))


def setup_logger(args_dict):
    logging_levels = {
        'notset':   logging.NOTSET,
        'debug':    logging.DEBUG,
        'info':     logging.INFO,
        'warning':  logging.WARNING,
        'error':    logging.ERROR,
        'critical': logging.CRITICAL,
    }
    log_filename = args_dict['log_filename']
    log_level = logging_levels[args_dict['log_level']]
    # setup logger object
    my_logger = logging.getLogger(args_dict['logger_name'])
    # set log level
    my_logger.setLevel(log_level)
    # handler
    handler = logging.handlers.RotatingFileHandler(
        log_filename,
        maxBytes=args_dict['max_log_size'],
        backupCount=args_dict['backup_count']
    )
    formatter = logging.Formatter(args_dict['log_format'])
    handler.setFormatter(formatter)
    # add handler to logger
    my_logger.addHandler(handler)
    # return logger object
    return my_logger


#def thread_log_function(app_args, queue_log):
def process_log_function(app_args, queue_log):
    logger = setup_logger(app_args)
    while True:
        bf = queue_log.get()
        if bf is None:
            break
        level, log_info = bf
        if level.lower() == 'debug':
            logger.debug(log_info)
        elif level.lower() == 'info':
            logger.info(log_info)
        elif level.lower() == 'warning':
            logger.info(log_info)
        elif level.lower() == 'error':
            logger.info(log_info)
        elif level.lower() == 'critical':
            logger.info(log_info)


def process_function(queue_log, i):
    pid = os.getpid()
    info = 'subprocess [{:d}] started.'.format(pid)
    my_print(info)
    queue_log.put(('info', info))
    for j in range(100):
        info = 'subprocess [{:d}]: {:d} this is test message come ' \
               'from subprocess and number {:d}.'.format(pid, j, i)
        queue_log.put(('info', info))
    info = 'subprocess [{:d}] stopped.'.format(pid)
    my_print(info)
    queue_log.put(('info', info))


def main():
    app_args = {
        'logger_name':  'test07_01',
        'log_filename': 'test07_01.log',
        'log_level':    'debug',
        'max_log_size': 50000,   # 50kb
        'backup_count': 5,
        'log_format':   '%(asctime)s - %(name)s - %(levelname)s :: '
                        '%(message)s',
    }
    max_processes = 10

    queue_log = multiprocessing.Queue()
    #thread_log = threading.Thread(target=thread_log_function,
    #                              args=(app_args, queue_log))
    process_log = multiprocessing.Process(target=process_log_function,
                                          args=(app_args, queue_log))
    #thread_id = None
    process_log_id = None

    try:
        #thread_log.start()
        process_log.start()
        #thread_id = thread_log.ident
        process_log_id = process_log.ident
        #info = 'thread [{:d}] started.'.format(thread_id)
        info = 'Process log [{:d}] started.'.format(process_log_id)
        my_print(info)
        queue_log.put(('info', info))

        processes = [multiprocessing.Process(target=process_function,
                                             args=(queue_log, i,))
                     for i in range(max_processes)]

        for p in processes:
            p.start()

        for p in processes:
            p.join()

    except Exception as exception:
        exception_info = str(exception)
        my_print(exception_info)
        queue_log.put(('info', exception_info))

    finally:
        #if thread_log.is_alive:
        if process_log.is_alive():
            queue_log.put(None)
            #thread_log.join()
            process_log.join()
            #info = 'Thread [{:d}] stopped.'.format(thread_id)
            info = 'Process log [{:d}] stopped.'.format(process_log_id)
            my_print(info)


if __name__ == '__main__':
    main()
    my_print('Done')
