#
# Simple example which uses a pool of workers to carry out some tasks.
#

import time
import random

from multiprocessing import Process, Queue, current_process, freeze_support


# Function run by worker processes
def worker(input_queue, output_queue):
    for func, args in iter(input_queue.get, 'STOP'):
        result = calculate(func, args)
        output_queue.put(result)


class Worker(object):

    def some_method(self, input_queue, output_queue):
        for func, args in iter(input_queue.get, 'STOP'):
            result = calculate(func, args)
            output_queue.put(result)

# Function used to calculate result
def calculate(func, args):
    result = func(*args)
    msg = '%s says that %s%s = %s' % (
        current_process().name,
        func.__name__,
        args,
        result
    )
    return msg


# Functions referenced by tasks
def mul(a, b):
    time.sleep(0.5 * random.random())
    return a * b


def plus(a, b):
    time.sleep(0.5 * random.random())
    return a + b


# test
def test():

    processes_count = 4

    tasks1 = [(mul, (i, 7)) for i in range(20)]
    tasks2 = [(plus, (i, 8)) for i in range(10)]

    # Create queues
    task_queue = Queue()
    done_queue = Queue()

    # Submit tasks
    for t in tasks1:
        task_queue.put(t)

    # Start worker processes
    W = Worker()
    for _ in range(processes_count):
        Process(
            #target=worker,
            target=W.some_method,
            args=(task_queue, done_queue)
        ).start()

    # Get and print results
    print 'Unordered results:'

    for _ in range(len(tasks1)):
        print '\t', done_queue.get()

    # Add more tasks using `put()`
    for t in tasks2:
        task_queue.put(t)

    # Get and print some more results
    for _ in range(len(tasks2)):
        print '\t', done_queue.get()

    # Tell child processes to stop
    for _ in range(processes_count):
        task_queue.put('STOP')


if __name__ == '__main__':
    freeze_support()
    test()
