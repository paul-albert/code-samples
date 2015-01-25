class RegistryPattern (object) :
  
  data = {}
  
  @staticmethod
  def __set__ (variable, value) :
    RegistryPattern.data[variable] = value
    
  @staticmethod
  def __setitem__ (variable, value) :
    RegistryPattern.data[variable] = value
  
  @staticmethod
  def __get__ (variable) :
    return RegistryPattern.data[variable]
  
  @staticmethod    
  def __getitem__ (variable) :
    return RegistryPattern.data[variable]
  
  @staticmethod
  def __delete__ (variable) :
    del(RegistryPattern.data[variable])

registry = RegistryPattern()

registry.e = 123
print registry.e

registry['e'] = 'qwerty'
print registry['e']
