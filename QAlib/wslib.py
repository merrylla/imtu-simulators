"""
Generic WEB Service support tools
"""

import sys
from os.path import basename
import logging
import datetime
import sqlite3

def timeNow():
    format="%b %d %H:%M:%S "
    today = datetime.datetime.today()
    return today.strftime(format)

def slog(msg, loglevel=logging.DEBUG):
    fn='/u/debit/logs/'+basename(sys.argv[0])+'.log'
    logging.basicConfig(filename=fn,level=loglevel)
    newmsg=timeNow()+msg
    logging.debug(newmsg)

def delDB(app, key):
   try:
      conn = sqlite3.connect('/u/debit/apps/htdocs/QAdata/db/QAImtu.db')
      c = conn.cursor()
      stmt="delete from xmlData where app='%s' and key='%s'" % (app, key)
      c.execute(stmt)
   except sqlite3.Error as e:
      slog ("ERROR: delDB sqlite3.connect %s" % e.args[0])


def store2DB(app, key, content):
   try:
      conn = sqlite3.connect('/u/debit/apps/htdocs/QAdata/db/QAImtu.db')
      c = conn.cursor()
      stmt="delete from xmlData where app='%s' and key='%s'" % (app, key)
      c.execute(stmt)
      stmt="insert into xmlData (app,key,xml) values('%s','%s',\"%s\")" % (app, key, content)
      #slog ("INFO: store2DB: stmt %s" % stmt)
      c.execute(stmt)
      conn.commit()
      c.close()
   except sqlite3.Error as e:
      slog ("ERROR: store2DB sqlite3.connect %s" % e.args[0])

def readDB(k1, k2):
   slog ("readDB: retrieve data for app:<%s> key:<%s>" % (k1, k2))
   try:
      conn = sqlite3.connect('/u/debit/apps/htdocs/QAdata/db/QAImtu.db')
      c = conn.cursor()
      stmt="select xml from xmlData where app='%s' and key='%s'" % (k1, k2)
      slog ("INFO: readDB stmt %s" % stmt)
      c.execute(stmt)
      d=c.fetchone()
      if d == None:
         return None
      #slog ("INFO: readDB from DB <%s>" % str(d[0]))
      return str(d[0])
   except sqlite3.Error as e:
      slog ("ERROR: readDB sqlite3.connect %s" % e.args[0])
      return None

def decodeXml(xl):
    if xl == None:
       return None
    import xml.etree.ElementTree as ET
    root=ET.fromstring(xl)
    parmList={}
    for child in root:
        parmList[child.tag]= child.text

    if (len(parmList) == 0):
       return None
    return parmList

def ckInstrFromDB(k1, k2):
    return decodeXml(readDB(k1, k2))

def ckStdin(timeout = 300):
    from select import select
    #slog('ckStdin - set wait time to <%d>' % timeout)
    rlist, _, _ = select([sys.stdin], [], [], timeout)
    if rlist:
       return True
    else:
       slog ('ckStdin found no data. return False!!')
       return False




def getdataC(eotC):
    slog('getdataC starting')
    if ckStdin() == False:
       slog('getdata:ckStdin- nothing to read - raise SystemExit!')
       raise SystemExit
    data=''
    c=''
    while c != eotC:
       c=sys.stdin.read(1)
       data += c
    return data

def getdataD(cc, waitTime):
    #slog('getdata starting - terminating at <0x%s>' % cc.encode('hex'))
    if ckStdin(waitTime) == False:
       slog('getdata:ckStdin- nothing to read - raise SystemExit!')
       raise SystemExit
    data=''
    c=''
    l=-len(cc)
    while data[l:] != cc:
       c=sys.stdin.read(1)
       if c=='':
          raise SystemExit('Read <%d> bytes - Connection disconnected by peer.' % len(data))        
       data += c
    return data

def getdataLen(sz):
    slog('getdata starting, expect to read <%d> bytes' % sz)
    if ckStdin() == False:
       slog('getdata:ckStdin- nothing to read - exiting!')
       raise SystemExit
    slog('getdata: found data to read - reading data!')
    data = sys.stdin.read(sz)
    slog('getdata data read<%s> bytes' % len(data))
    return data

def delayRsp(delay, abort):
    if delay > 0:
       from time import sleep
       slog ('Received instruction to delay rsp <%d> seconds.' % delay)
       sleep(delay)
    if abort == True:
       slog ('Received instruction abort <%s>' % abort)
       raise SystemExit

