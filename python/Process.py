# -*- coding: utf-8 -*-

import multiprocessing


class Process(multiprocessing.Process):
    """
    Class for parsing process
    """

    def __init__(self, jobs_queue, results_queue, settings, db_settings,
                 console_log, debug_log, error_log):
        """
        Initializes parsing process.

        @type  jobs_queue: multiprocessing.Queue
        @param jobs_queue: queue of jobs in multiprocessing pool
        @type  results_queue: multiprocessing.Queue
        @param results_queue: queue of results in multiprocessing pool
        @type  settings: dict
        @param settings: dictionary with all settings
        @type  db_settings: dict
        @param db_settings: dictionary with database settings
        @type  debug_log: multiprocessing.JoinableQueue
        @param debug_log: debug logging queue
        @type  error_log: multiprocessing.JoinableQueue
        @param error_log: errors logging queue
        """
        super(Process, self).__init__()  # call parent's constructor
        self.jobs_queue = jobs_queue
        self.results_queue = results_queue
        self.settings = settings
        self.db_settings = db_settings
        self.console_log = console_log
        self.debug_log = debug_log
        self.error_log = error_log

    def run(self):
        """
        Runs parsing process and works with its queues.

        @return: void
        """
        while True:
            next_job = self.jobs_queue.get()
            if next_job is None:
                # None is a 'poison pill' that means shutdown of process
                self.jobs_queue.task_done()
                break
            job_result = next_job.call(self.settings, self.db_settings,
                                       self.debug_log, self.error_log)
            s = next_job.get_request_result()
            self.console_log.put(s)
            self.debug_log.put(s)
            self.jobs_queue.task_done()
            self.results_queue.put(job_result)
        return
