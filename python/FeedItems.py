# -*- coding: utf-8 -*-

import urlparse


class FeedItem(object):
    """
    Class for single RSS feed item
    """

    def __init__(self, base_url, item, encoding):
        """
        Constructor.

        @type  base_url: str
        @param base_url: base url
        @type  item: dict
        @param item: RSS feed item
        @type  encoding: str
        @param encoding: base encoding
        """
        self.base_url = base_url
        self.item = item
        self.encoding = encoding

    def get(self, key):
        """
        Gets value for RSS feed item by key.

        @type  key: str
        @param key: key in RSS feed item's storage

        @rtype:  str
        @return: value for given key
        """
        if key in self.item and len(self.item[key]) > 0:
            try:
                result = self.item[key]
                if key == 'link':
                    result = result.decode(self.encoding)
                    # additional check if is url absolute
                    # (although usually feed parser can self to convert
                    # relative url to absolute, but we do it just in case)
                    if not bool(urlparse.urlparse(result).netloc):
                        # if no, then "glue" this relative url with base url
                        result = urlparse.urljoin(self.base_url, result)
            except:
                result = ''
        else:
            result = ''
        return result


class FeedItems(object):
    """
    Class for set of RSS feed items (as list)
    """
    # fields in items that we need
    FIELDS = ['published', 'updated', 'created', 'modified', 'expired', 'date',
              'link', 'title', 'summary']

    @staticmethod
    def filter_items(items):
        """
        Filters RSS feed items list from non-interesting fields.

        @type  items: list
        @param items: RSS feed items list

        @rtype:  list
        @return: filtered items
        """
        filtered_items = []
        for item in items:
            filtered_item = dict()
            for k, v in item.iteritems():
                if k in FeedItems.FIELDS:
                    filtered_item[k] = v
            filtered_items.append(filtered_item)
        return filtered_items

    @staticmethod
    def sort_items(items):
        """
        Sorts RSS feed items list (by timestamp field).

        @type  items: list
        @param items: RSS feed items list

        @rtype:  list
        @return: filtered items
        """
        decorated = [(item['timestamp'], item) for item in items]
        decorated.sort()
        decorated.reverse()
        return [item for (parsed_timestamp, item) in decorated]
