import sqlite3

try:
  conn = sqlite3.connect('../QAdata/db/QAImtu.db')
  c = conn.cursor()
  c.execute('''CREATE TABLE xmlData
             (app text, key text, xml text, date text, misc text)''')
except sqlite3.Error as e:
  print "An error occurred:", e.args[0]
  pass

#stmt='select * from xmlData'
#c.execute(stmt)
#print 'c.fetchone'
#row= c.fetchone()
#print row
#newrow=('appB', 'xml data2', '', '', '')
#print 'executemany'
#print c.executemany('insert into xmlData values(?,?,?,?,?)', [newrow])
#for row in c.execute(stmt):
#    print row
