# -*- coding: utf-8 -*-

import MySQLdb
import MySQLdb.cursors
from contextlib import closing


class Db(object):
    """
    Main class for work with database
    """

    def __init__(self, db_settings=None):
        """
        Initializes database connection.

        @type  db_settings: dict; None by default
        @param db_settings: settings for db connection
        """
        self.dbConnection = None
        self.dbCursor = None
        self.dbCursorClass = MySQLdb.cursors.Cursor
        self.dbSettings = db_settings
        self.connect()

    def connect(self):
        """
        Creation of connection to database.
        """
        try:
            self.dbConnection = MySQLdb.connect(**self.dbSettings)
        except MySQLdb.Error:
            pass

    def set_cursor(self):
        """
        Recreates/pings connection and sets cursor for database.
        """
        try:
            self.dbConnection.ping()
        except MySQLdb.Error:
            self.connect()
        finally:
            self.dbCursor = self.dbConnection.cursor(
                cursorclass=self.dbCursorClass)

    def row(self, sql):
        """
        Fetches one row only.

        @type  sql: str
        @param sql: SQL-query for fetch a row

        @rtype:  tuple
        @return: tuple with query results
        """
        self.set_cursor()
        with closing(self.dbCursor) as dbCursor:
            dbCursor.execute(sql)
            row = dbCursor.fetchone()  # result as tuple
        return row

    def rows(self, sql):
        """
        Fetches all rows.

        @type  sql: str
        @param sql: SQL-query for rows fetching

        @rtype:  list
        @return: list of tuples with query results
        """
        self.set_cursor()
        with closing(self.dbCursor) as dbCursor:
            dbCursor.execute(sql)
            rows = dbCursor.fetchall()  # return as list of tuples
        return rows

    def query_single(self, sql, data=None):
        """
        Single execute query for insert, update, delete.

        @type  sql: str
        @param sql: SQL-query for rows fetching

        @type  data: list
        @param data: list of tuples with data; None by default

        @rtype:  int
        @return: count of affected rows
        """
        self.set_cursor()
        with closing(self.dbCursor) as dbCursor:
            try:
                affected_rows_count = int(dbCursor.execute(sql, data))
                # for make sure data is committed to the database
                self.dbConnection.commit()
            except MySQLdb.Error:
                # if any errors then roll back changes in database
                self.dbConnection.rollback()
                affected_rows_count = 0
        return affected_rows_count

    def query_multi(self, sql, data=None):
        """
        Bulk-mode execute query for insert, update, delete.

        @type  sql: str
        @param sql: SQL-query for rows fetching

        @type  data: list
        @param data: list of tuples with data; None by default

        @rtype:  int
        @return: count of affected rows
        """
        self.set_cursor()
        with closing(self.dbCursor) as dbCursor:
            try:
                affected_rows_count = int(dbCursor.executemany(sql, data))
                # for make sure data is committed to the database
                self.dbConnection.commit()
            except MySQLdb.Error:
                # if any errors then roll back changes in database
                self.dbConnection.rollback()
                affected_rows_count = 0
        return affected_rows_count

    def table_exists(self, table_name):
        """
        Checks if database table exists or not.

        @type  table_name: str
        @param table_name: database table name

        @rtype:  bool
        @return: True/False for database table existence
        """
        table_name = table_name.replace('\'', '\'\'')
        sql = """
            SELECT COUNT(1=1)
            FROM `information_schema`.`tables`
            WHERE
                `table_schema` = '{table_schema}'
                AND
                `table_name` = '{table_name}'
        """.format(table_schema=self.dbSettings['db'], table_name=table_name)
        result = self.row(sql)
        return True if result[0] == 1 else False

    def close(self):
        """
        Tries to close opened MySQL connection handle.
        """
        try:
            self.dbConnection.close()
        except MySQLdb.Error:
            pass

    def __del__(self):
        """
        Destructor.
        """
        self.close()
