# Python metaclass registry pattern

class MetaRegister(type):
    REGISTRY = {}
    def __new__(cls, name, bases, attrs):
        """ @param name: Name of the class
            @param bases: Base classes (tuple)
            @param attrs: Attributes defined for the class 
        """
        new_cls = type.__new__(cls, name, bases, attrs)
        cls.REGISTRY[name] = new_cls
        return new_cls

class BaseClass(object):
    __metaclass__ =  MetaRegister

class MyClass(BaseClass):pass

class YourClass(BaseClass): pass

print MetaRegister.REGISTRY

'''
it will output:
{'MyClass': <class '__main__.MyClass'>, 'BaseClass': <class '__main__.BaseClass'>, 'YourClass': <class '__main__.YourClass'>}
'''