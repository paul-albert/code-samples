# -*- coding: utf-8 -*-

import urlparse
import re


class Url(object):
    """
    Class for work with URLs
    """

    STRIP_URL_RE = r'[\n\t\r]'

    @staticmethod
    def to_ascii(url):
        """
        Converts non-ascii url to ascii through IDNA.
        (it's known problem for non-latin urls, e.g. with cyrillic domains)

        @type  url: str
        @param url: url for convert

        @rtype:  str
        @return: converted url
        """
        # step 1 - parse source url into its components
        # step 2 - for each component of url encode into IDNA encoding
        # step 3 - "glue" all encoded components of url
        return urlparse.urlunparse([u.encode('idna')
                                    for u in urlparse.urlparse(url)])

    @staticmethod
    def strip(url):
        """
        Strips url from unwanted characters.

        @type  url: str
        @param url: url for strip

        @rtype:  str
        @return: stripped url
        """
        unwanted_chars = re.compile(Url.STRIP_URL_RE)
        return unwanted_chars.sub('', url).strip()
