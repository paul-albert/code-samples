#!/usr/bin/env python
# -*- coding: utf-8 -*-

from multiprocessing import Process, JoinableQueue as Queue


class Worker(Process):
    """
    Process executing tasks from a given tasks queue
    """

    def __init__(self, tasks):
        super(Worker, self).__init__()
        self.tasks = tasks
        self.daemon = True
        self.start()

    def run(self):
        while True:
            func, args, kwargs = self.tasks.get()
            try:
                func(*args, **kwargs)
            except Exception, e:
                print e
            finally:
                self.tasks.task_done()


class ProcessPool:
    """
    Pool of processes consuming tasks from a queue
    """

    def __init__(self, num_processes):
        self.tasks = Queue(num_processes)
        for _ in range(num_processes):
            Worker(self.tasks)

    def add_task(self, func, *args, **kwargs):
        """
        Add a task to the queue
        """
        self.tasks.put((func, args, kwargs))

    def wait_completion(self):
        """Wait for completion of all the tasks in the queue"""
        self.tasks.join()


if __name__ == '__main__':

    from random import randrange
    from time import sleep

    delays = [randrange(1, 10) for i in range(100)]

    def wait_delay(delay):
        msg = 'sleeping for {:d} sec'.format(delay)
        print msg
        sleep(delay)

    pool = ProcessPool(20)

    for i, d in enumerate(delays):
        pool.add_task(wait_delay, d)

    pool.wait_completion()
