#!/usr/bin/env python
# -*- coding: utf-8 -*-

import random
from multiprocessing import Pool, Manager


def worker(task, already_done):
    if task in already_done:
        print 'task %d is already done' % task
    else:
        already_done[task] = True
        print 'do %d' % task


def main():
    MAX_PROCESSES = 4
    
    manager = Manager()
    already_done = manager.dict()
    
    pool = Pool(processes=MAX_PROCESSES)

    for _ in xrange(100):
        task = random.randint(1, 10)
        pool.apply_async(worker, (task, already_done))

    pool.close()
    pool.join()

  
if __name__ == '__main__':
    main()
