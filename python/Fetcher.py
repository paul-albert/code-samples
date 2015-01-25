# -*- coding: utf-8 -*-

import pycurl
import cStringIO
import re
import time


class Fetcher(object):
    """
    Class for fetching of RSS feed
    """

    @staticmethod
    def execute(request, settings):
        """
        Fetches information from RSS feed (through flexible Curl module).

        @type  request: dict
        @param request: request information dictionary
        @type  settings: dict
        @param settings: settings dictionary

        @rtype:  dict
        @return: fetched information for RSS feed
        """
        start = time.time()

        def _strip_url(url):  # inside function
            """
            Strips url from unwanted characters.

            @type  url: str
            @param url: url for strip

            @rtype:  str
            @return: stripped url
            """
            unwanted_chars = re.compile(r'[\n\t\r]')
            return unwanted_chars.sub('', url).strip()

        def _curl_setopt(curl_handle, buff, url,
                         http_headers=()):  # inside function
            """
            Sets some options for Curl handle.

            @type  curl_handle: pycurl.Curl
            @param curl_handle: Curl handle for options setting
            @type  buff: cStringIO.StringIO
            @param buff: buffer stream for strings handling
            @type  url: str
            @param url: url for fetch
            @type  http_headers: tuple
            @param http_headers: tuple for HTTP headers; empty by default

            @rtype:  pycurl.Curl
            @return: tuned Curl handle
            """
            curl_handle.setopt(pycurl.HTTP_VERSION,
                               pycurl.CURL_HTTP_VERSION_1_1)
            curl_handle.setopt(pycurl.VERBOSE,        0)
            curl_handle.setopt(pycurl.FOLLOWLOCATION, 1)
            curl_handle.setopt(pycurl.MAXREDIRS,      10)
            curl_handle.setopt(pycurl.AUTOREFERER,    1)
            curl_handle.setopt(pycurl.CONNECTTIMEOUT,
                               int(settings['connect_timeout']))
            curl_handle.setopt(pycurl.TIMEOUT,        int(settings['timeout']))
            if settings['proxy']:
                curl_handle.setopt(pycurl.PROXY,      str(settings['proxy']))
                # assuming that we use HTTP proxy, no SOCKS 4/5
                curl_handle.setopt(pycurl.PROXYTYPE,  pycurl.PROXYTYPE_HTTP)
            curl_handle.setopt(pycurl.NOSIGNAL,       1)
            curl_handle.setopt(pycurl.HEADER,         0)
            curl_handle.setopt(pycurl.SSL_VERIFYPEER, 0)
            curl_handle.setopt(pycurl.SSL_VERIFYHOST, 0)
            curl_handle.setopt(pycurl.USERAGENT,
                               str(settings['user_agent']))
            if http_headers:
                curl_handle.setopt(pycurl.HTTPHEADER, http_headers)
            curl_handle.setopt(pycurl.URL,            url)
            curl_handle.setopt(c.WRITEFUNCTION,       buff.write)
            return curl_handle

        request['url'] = _strip_url(request['url'])
        #http_headers = ('Accept-Charset: UTF-8', 'Connection: keep-alive')
        http_headers = ()
        str_buffer = cStringIO.StringIO()
        c = pycurl.Curl()
        c = _curl_setopt(c, str_buffer, request['url'], http_headers)
        try:
            c.perform()
            response_content = str_buffer.getvalue()
            response_code = int(c.getinfo(pycurl.HTTP_CODE))
        except:
            response_content = ''
            response_code = 0
        str_buffer.close()
        c.close()
        return dict({
            #'number':           request['number'],
            'url':              request['url'],
            'response_code':    response_code,
            'response_content': response_content,
            'elapsed':          time.time() - start,
        })
