#!/usr/bin/env python

"""
test06.py -- producer adds to fixed-sized list; scanner uses them

OPTIONS:
-v  verbose multiprocessing output
"""

import logging
import logging.handlers
import multiprocessing
import sys
import time


LOG_FILENAME = 'test06.log'


def producer(obj_list):
    """
    add an item to list every 2 sec; ensure fixed size list
    """
    logger = multiprocessing.get_logger()
    logger.info('start')
    while True:
        try:
            time.sleep(0.5)
        except KeyboardInterrupt:
            return
        msg = 'ding: {:04d}'.format(int(time.time()) % 10000)
        logger.info('put: %s', msg)
        del obj_list[0]
        obj_list.append(msg)


def scanner(obj_list):
    """
    every now and then, run calculation on obj_list
    """
    logger = multiprocessing.get_logger()
    logger.info('start')
    while True:
        try:
            time.sleep(5)
        except KeyboardInterrupt:
            return
        logger.info('items: %s', list(obj_list))


def main():
    opt_verbose = '-v' in sys.argv[1:]
    logger = multiprocessing.log_to_stderr(
        level=logging.DEBUG if opt_verbose else logging.INFO,
    )
    handler = logging.handlers.RotatingFileHandler(LOG_FILENAME, maxBytes=1024,
                                                   backupCount=5)
    logger.addHandler(handler)

    logger.info('setup')

    # create fixed-length list, shared between producer & consumer
    manager = multiprocessing.Manager()
    my_obj_list = manager.list([None] * 10)

    multiprocessing.Process(target=producer, args=(my_obj_list,),
                            name='producer',).start()

    multiprocessing.Process(target=scanner, args=(my_obj_list,),
                            name='scanner',).start()

    logger.info('running forever')
    try:
        manager.join()  # wait until both workers die
    except KeyboardInterrupt:
        pass
    logger.info('done')
    

if __name__ == '__main__':
    main()
