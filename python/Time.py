# -*- coding: utf-8 -*-

import time
import datetime
import types
from timelib import strtotime
from math import modf


class Time(object):
    """
    Time class
    """
    FORMAT_DEFAULT = '%Y-%m-%d %H:%M:%S'  # default format for date/time

    @staticmethod
    def get_utc(time_format=None, add_minutes=0):
        """
        Returns time in UTC timezone.

        @type  time_format: str
        @param time_format: string with format of time
        @type  add_minutes: int
        @param add_minutes: number of minutes for add or sub

        @rtype:  str
        @return: time in UTC timezone
        """
        if time_format is None:
            time_format = Time.FORMAT_DEFAULT
        timestamp = time.time()
        if add_minutes != 0:
            timestamp += add_minutes * 60  # 1 minute = 60 seconds
        return Time.gmt_date(time_format, timestamp)

    @staticmethod
    def get_numeric_date(timestamp):
        """
        Returns date for timestamp.

        @type  timestamp: str/int
        @param timestamp: timestamp (in format UNIX-timestamp)

        @rtype:  str
        @return: date
        """
        return Time.gmt_date('%Y%m%d', timestamp)

    @staticmethod
    def format_timestamp(timestamp, time_format=None):
        """
        Returns formatted timestamp.

        @type  timestamp: str/int
        @param timestamp: timestamp (in format UNIX-timestamp)
        @type  time_format: str
        @param time_format: format for timestamp; None by default

        @rtype:  str
        @return: formatted timestamp
        """
        if time_format is None:
            time_format = Time.FORMAT_DEFAULT
        return Time.gmt_date(time_format, timestamp)

    @staticmethod
    def str_to_time(date):
        """
        Returns timestamp for date.

        @type  date: str/unicode
        @param date: string with date (can be relative too, example: 'today',
                                      '-1 day' and so on)

        @rtype:  int
        @return: converted date as timestamp
        """
        return 0 if isinstance(date, types.NoneType) else strtotime(date)

    @staticmethod
    def micro_time(get_as_float=False):
        """
        Returns micro-time (i.e. time with microseconds).

        @type  get_as_float: bool
        @param get_as_float: True if we want to get as float number,
                             otherwise False; False by default

        @rtype:  float/str
        @return: converted date as timestamp
        """
        return time.time() if get_as_float else '%f %d' % modf(time.time())

    @staticmethod
    def gmt_date(time_format, timestamp=None):
        """
        Returns formatted date and/or time in GMT timezone.

        @type  time_format: str
        @param time_format: format for date and/or time
        @type  timestamp: str/int
        @param timestamp: timestamp that needs to be formatted;
                          None by default

        @rtype:  str
        @return: formatted date and/or time in GMT timezone
        """
        if timestamp is None:
            gmt_time = time.gmtime()  # current time in GMT timezone
        else:
            gmt_time = time.gmtime(int(timestamp))
        return time.strftime(time_format, gmt_time)

    @staticmethod
    def timestamp_to_human(timestamp, time_format=None):
        """
        Converts timestamp to human-readable date/time.

        @type  timestamp: str/int
        @param timestamp: timestamp (in format UNIX-timestamp)
        @type  time_format: str
        @param time_format: format for timestamp; None by default

        @rtype:  str
        @return: formatted timestamp
        """
        if time_format is None:
            time_format = Time.FORMAT_DEFAULT
        return datetime.datetime.fromtimestamp(timestamp).strftime(time_format)
