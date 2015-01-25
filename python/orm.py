import string
import new
import MySQLdb
from types import *
from MySQLdb.cursors import DictCursor

bag_belongs_to, bag_has_many = [], []


def belongs_to(what):
    bag_belongs_to.append(what)


def has_many(what):
    bag_has_many.append(what)


class MysqlWrapper:

    def __init__(self, **kwargs):
        self.conn = MySQLdb.connect(cursorclass=DictCursor, **kwargs)
        self.cursor = self.conn.cursor()
        self.escape = self.conn.escape_string
        self.insert_id = self.conn.insert_id
        self.commit = self.conn.commit
        self.q = self.cursor.execute

    def query_one(self, query):
        self.q(query)
        return self.cursor.fetchone()

    def query_all(self, query):
        self.q(query)
        return self.cursor.fetchall()


class MetaRecord(type):

    def __new__(cls, name, bases, dct):
        global bag_belongs_to, bag_has_many
        if name in globals():
            return globals()[name]
        else:
            record = type.__new__(cls, name, bases, dct)
            for i in bag_belongs_to:
                record.belongs_to(i)
            for i in bag_has_many:
                record.has_many(i)
            bag_belongs_to = []
            bag_has_many = []
            return record


class Orm(dict):

    __metaclass__ = MetaRecord
    __CONN = None

    @classmethod
    def belongs_to(cls, what):
        def dah(self):
            belong_cls = globals().get(what, None)
            if not belong_cls:
                belong_cls = type(what, (Orm,), {})
            return belong_cls.select_one(self[what + '_id'])
        setattr(cls, what, new.instancemethod(dah, None, cls))

    @classmethod
    def has_many(cls, what):
        def dah(self):
            has_many_cls = globals().get(what, None)
            if not has_many_cls:
                has_many_cls = type(what, (Orm,), {})
            d = dict()
            d[string.lower(cls.__name__) + '_id'] = self['id']
            return has_many_cls.select(**d)
        setattr(cls, what, new.instancemethod(dah, None, cls))

    @classmethod
    def conn(cls, **kwargs):
        if not cls.__CONN:
            cls.__CONN = MysqlWrapper(**kwargs)

    @classmethod
    def exe(cls, s):
        if not cls.__CONN:
            raise "Database not connected"
        return cls.__CONN.query_all(s)

    @classmethod
    def insert(cls, **kwargs):
        vs = [[k, cls.__CONN.escape(str(kwargs[k]))] for k in kwargs]
        if vs:
            s = "insert into %s (%s) values ('%s')" % (
                string.lower(cls.__name__), ','.join([v[0] for v in vs]),
                "','".join([v[1] for v in vs])
            )
            cls.__CONN.q(s)
            cls.__CONN.commit()
            return cls.__CONN.insert_id()
        else:
            raise "nothing to insert"

    @classmethod
    def select(cls, *args, **kwargs):
        if len(args) == 1\
            and (type(args[0]) == IntType or type(args[0]) == LongType):
            q = "select * from %s where id='%s'" % (string.lower(cls.__name__),
                                                    args[0])
            where = "where id='%s'" % args[0]
        else:
            if args:
                s = ",".join(args)
            else:
                s = "*"

            if kwargs:
                c, limit, order_by = [], '', ''
                for k in kwargs:
                    if k == 'limit':
                        limit = "limit " + str(kwargs[k])
                    elif k == 'order':
                        order_by = "order by " + str(kwargs[k])
                    else:
                        c.append(k + "='" + str(kwargs[k]) + "'")
                where = " and ".join(c)
                if where:
                    where = "where %s" % where
                where = "%s %s %s" % (where, order_by, limit)
            else:
                where = ""

            q = " ".join([
                'select', s, 'from', string.lower(cls.__name__), where
            ])

        r = cls.__CONN.query_all(q)
        l = []
        for i in r:
            l.append(cls(i))
            l[-1].__dict__['where'] = where
        return l

    @classmethod
    def select_one(cls, *args, **kwargs):
        r = cls.select(*args, **kwargs)
        if r:
            return r[0]
        else:
            return {}

    @classmethod
    def update(cls, cond, **kwargs):
        if not cond or not kwargs:
            raise "Update What?!"
        if type(cond) == IntType:
            w = "id='%d'" % cond
        else:
            w = cond
        vs = [[k, cls.__CONN.escape(str(kwargs[k]))] for k in kwargs]
        if vs:
            s = "update %s set %s where %s" % (string.lower(cls.__name__),
                ','.join(["%s='%s'" % (v[0], v[1]) for v in vs]), w)
            cls.__CONN.q(s)
            cls.__CONN.commit()

    @classmethod
    def delete(cls, id):
        if type(id) == IntType:
            cls.__CONN.q(
                "delete from %s where id='%d'" %
                (string.lower(cls.__name__), id)
            )
            cls.__CONN.commit()
        else:
            raise "Only accept integer argument"

    def __init__(self, d={}):
        if not self.__class__.__CONN:
            raise "Database not connected"
        dict.__init__(self, d)
        self.__dict__['cur_table'] = string.lower(self.__class__.__name__)
        self.__dict__['where'] = ''
        self.__dict__['sql_buff'] = {}

    def sql(self, sql):
        self.__class__.__CONN.q(sql)

    def save(self):
        s = ""
        if self.where:
            f = []
            for v in self.sql_buff:
                f.append("%s='%s'" % (v, self.sql_buff[v]))
            s = "update %s set %s %s" %\
                (self.cur_table, ','.join(f), self.where)
        else:
            f, i = [], []
            for v in self.sql_buff:
                f.append(v)
                i.append(self.sql_buff[v])
            if f and i:
                s = "insert into %s (%s) values ('%s')" %\
                    (self.cur_table, ','.join(f), "','".join(i))

        if s:
            self.__class__.__CONN.q(s)
            self.__class__.__CONN.commit()
        else:
            raise "nothing to insert"

    def __setattr__(self, attr, value):
        if attr in self.__dict__:
            self.__dict__[attr]=value
        else:
            v = self.__class__.__CONN.escape(str(value))
            self.__dict__['sql_buff'][attr] = v
            self[attr] = v

    def __getattr__(self, attr):
        if attr in self.__dict__:
            return self.__dict__[attr]
        try:
            return self[attr]
        except KeyError:
            pass
        raise AttributeError


__all__ = ['Orm', 'belongs_to', 'has_many']
