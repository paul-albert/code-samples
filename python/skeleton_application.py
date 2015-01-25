#!/usr/bin/env python
# -*- coding: utf-8 -*-

import random
import sys
import time
from multiprocessing import Lock, Process, Queue


P = 8                       # the number of processes we want to launch
J = 20                      # the number of jobs we want to process


# slave function
def slave(procID, jobs, dispLock):
    try:
        while True:
            jobData = jobs.get_nowait()
            dispLock.acquire()
            sys.stdout.write('slave process %d ' % procID)
            sys.stdout.write('working in job %d\n' % jobData['jobID'])
            sys.stdout.flush()
            dispLock.release()

            # do real work here instead!
            time.sleep(random.random())
            #time.sleep(0.05)
    except:
        pass                # an exception is raised when job queue is empty


# master process entry logic
if __name__ == '__main__':
    pool = []               # instantiate pool of processes
    jobs = Queue()          # instantiate job queue
    display_lock = Lock()   # instantiate display lock (see previous example)

    # instantiate N slave processes
    for procID in range(P):
        pool.append(Process(target=slave, args=(procID, jobs, display_lock)))

    # populate the job queue
    for jobID in range(J):
        jobs.put({'jobID': jobID})

    # start the slaves
    for slave in pool:
        slave.start()

    # wait for the slaves to finish processing
    for slave in pool:
        slave.join()
