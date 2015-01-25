# -*- coding: utf-8 -*-

import multiprocessing
import time

from Process import Process
from Job import Job


class Pool(object):
    """
    Class for parsing processes pool (on base of multiprocessing)
    """

    def __init__(self, max_parallel_jobs_count, settings, db_settings,
                 console_log, debug_log, error_log):
        """
        Initializes parsing processes pool.

        @type  max_parallel_jobs_count: int
        @param max_parallel_jobs_count: max count for simultaneous jobs
        @type  settings: dict
        @param settings: dictionary with all settings
        @type  db_settings: dict
        @param db_settings: dictionary with database settings
        @type  debug_log: multiprocessing.JoinableQueue
        @param debug_log: debug logging queue
        @type  error_log: multiprocessing.JoinableQueue
        @param error_log: errors logging queue
        """
        super(Pool, self).__init__()
        self.jobs_queue = multiprocessing.JoinableQueue()
        self.results_queue = multiprocessing.Queue()
        self.max_parallel_jobs_count = max_parallel_jobs_count
        self.processes = []
        self.settings = settings
        self.db_settings = db_settings
        self.console_log = console_log
        self.debug_log = debug_log
        self.error_log = error_log

    def start(self):
        """
        Starts all jobs processes.
        """
        self.processes = [Process(self.jobs_queue, self.results_queue,
                                  self.settings, self.db_settings,
                                  self.console_log,
                                  self.debug_log, self.error_log)
                          for _ in range(self.max_parallel_jobs_count)]
        for p in self.processes:
            p.start()

    def add_request(self, request):
        """
        Adds parsing request to pool's jobs queue.

        @type  request: Request
        @param request: parsing request object
        """
        self.jobs_queue.put(Job(request))

    def wait_completion(self):
        """
        Waits until all jobs processes are completed.
        """
        self.jobs_queue.join()

    def close_jobs(self):
        """
        Closes all jobs processes (through "poison pill" of jobs queue).
        """
        # send a poison pill as None value
        for _ in range(self.max_parallel_jobs_count):
            self.jobs_queue.put(None)

    def collect_results(self):
        """
        Collects results list from parsing jobs results queue.

        @rtype:  list
        @return: parsing jobs results list
        """
        results = []
        delay = 0.005  # the small delay for more correct work of queue
                       # (if not to do that sleep, then may be some hangs for
                       # queue handling)
        time.sleep(delay)
        while self.results_queue.qsize() > 0:
            time.sleep(delay)
            results.append(self.results_queue.get())
        return results
