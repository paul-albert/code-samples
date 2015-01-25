# -*- coding: utf-8 -*-


import time
import types
from timelib import strtotime
from math import modf


class TimeUtils(object):
    """
    Time utils class
    """

    FORMAT_DEFAULT = '%Y-%m-%d %H:%M:%S'  # default format for date/time

    @staticmethod
    def get_utc(time_format=None, add_minutes=0):
        if time_format is None:
            time_format = TimeUtils.FORMAT_DEFAULT
        timestamp = time.time()
        if add_minutes != 0:
            timestamp += add_minutes * 60
        return TimeUtils.gmt_date(time_format, timestamp)

    @staticmethod
    def get_numeric_date(timestamp):
        return TimeUtils.gmt_date('%Y%m%d', timestamp)

    @staticmethod
    def format_timestamp(timestamp, time_format=None):
        if time_format is None:
            time_format = TimeUtils.FORMAT_DEFAULT
        return TimeUtils.gmt_date(time_format, timestamp)

    @staticmethod
    def str_to_time(date):
        #if str(date) == 'None':
        if isinstance(date, types.NoneType):
            return 0
        else:
            return strtotime(date)

    @staticmethod
    def micro_time(get_as_float=False):
        if get_as_float:
            return time.time()
        else:
            return '%f %d' % modf(time.time())

    @staticmethod
    def gmt_date(time_format, timestamp=None):
        if timestamp is None:
            return time.strftime(time_format, time.gmtime())
        else:
            return time.strftime(time_format, time.gmtime(int(timestamp)))
