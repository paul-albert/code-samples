#!/usr/bin/env python


NUMBER_OF_PRODUCER_PROCESSES = 5
NUMBER_OF_CONSUMER_PROCESSES = 5


from multiprocessing import Process, Lock, Queue
import random
import hashlib
import time
import os


class Consumer(object):

    def __init__(self):
        self.msg = None

    def consume_msg(self, producer_lock, queue):
        while True:
            print 'Got into consumer method, with pid: %s' % os.getpid()
            producer_lock.acquire()
            if queue.qsize() != 0:
                self.msg = queue.get()
                print 'got msg: %s' % self.msg
            else:
                self.msg = None
                print 'Queue looks empty'
            producer_lock.release()
            time.sleep(random.randrange(2, 4))


class Producer(object):

    def __init__(self):
        self.msg = None

    def produce_msg(self, consumer_lock, queue):
        while True:
            print 'Got into producer method, with pid: %s' % os.getpid()
            consumer_lock.acquire()
            self.msg = hashlib.md5(random.random().__str__()).hexdigest()
            queue.put(self.msg)
            print 'Produced msg: %s' % self.msg
            consumer_lock.release()
            time.sleep(random.randrange(2, 4))


def main():
    process_pool = []
    producer_lock = Lock()
    consumer_lock = Lock()
    queue = Queue()

    producer = Producer()
    consumer = Consumer()

    for i in (0, NUMBER_OF_PRODUCER_PROCESSES):
        p = Process(target=producer.produce_msg, args=(consumer_lock, queue,))
        process_pool.append(p)

    for i in (0, NUMBER_OF_CONSUMER_PROCESSES):
        p = Process(target=consumer.consume_msg, args=(producer_lock, queue,))
        process_pool.append(p)

    for each in process_pool:
        each.start()


if __name__ == "__main__":
    main()