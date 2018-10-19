#!/usr/local/bin/python
"""
NATCOM_VPG end point simulator
"""
import warnings

with warnings.catch_warnings():
    #warnings.filterwarnings("ignore",category=PowmInsecureWarning)
    warnings.filterwarnings("ignore",category=RuntimeWarning)
    from Crypto.PublicKey import RSA

import logging
import sys
import os
#import select
import datetime
from time import sleep

from ISO8583.ISO8583 import ISO8583
from ISO8583.ISOErrors import *
from socket import *

#import base64
from Crypto.Signature import PKCS1_v1_5
from Crypto.Hash import SHA
from Crypto.PublicKey import RSA

def b(s):
    return s

PRIVATE_PEM = b("""-----BEGIN RSA PRIVATE KEY-----
MIICXQIBAAKBgQDYnd8Ufz3ruvwM+ffj4Y7ofcsKH062wK72nQryEzdg4eNxPA3d
uNGQi6mmuBIfzBL8PDOU0G4eqd/Hf+yYv51VqeyYPJawTEkLGcFSLWhnttl79Lyf
4byRWVVGM5F6ozn019zrlH2vtl8WqgbgJ4+NgL+0NHCRveZMkvppwJPv6wIDAQAB
AoGBAIFqAEcMpf/Z3JAcH1+D+I8p6E4J2ksQ3vbzaACsPl+hVPLnwBkBPCKxbg/u
1NUuZQFRLjfwNnoXX1yfLskrYWEcs/InjXgZmtiCSNqS+2v4zBmjI2kLitbLUInj
ww3Gyc8/x+iYt26nmrRicEXpFvab5wHY9M+3Gy+9yftOM+6hAkEA+IvML251333I
a+IxvajXYQ1OeePAccmujJzC2welx8yIqsvIJ2aKyB4kyJm3V0VVm83fKmi2ASmG
fX4mO7eZ8wJBAN8c7+ke3h+ferfNBesWJo6uH/azmncaN9KSAf2O/JW0s+QAJkJ5
QwoLq8UFEVG3UXC1pHJnUBsu/feArS0smCkCQA0YaIizPDirSu0MPOyuQbLWXaUk
b9ZO9whnlgiuTXjKeQTuubwBthw3Il8DYlqRx6Hu5ew5GiXTh0eDZwZMdSsCQQC0
jumEwmtGMj0Q9AXfG1z2yOmrL+xIiNo2Od0MgyPcT5HpaUmNMC9VuN44ooJCnFV3
HjjIMN2+MghrvSHo1hcJAkBl6wDUA40mqnEuUod1RX1cHAgVgS61Z2DmiAu+a15q
cTx7Tpnth1xQciNvLpqj8/BqXTM/SnWM/K/dYVmyILxa
-----END RSA PRIVATE KEY-----
""")

PUBLIC_KEY = b("""-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDYnd8Ufz3ruvwM+ffj4Y7ofcsK
H062wK72nQryEzdg4eNxPA3duNGQi6mmuBIfzBL8PDOU0G4eqd/Hf+yYv51VqeyY
PJawTEkLGcFSLWhnttl79Lyf4byRWVVGM5F6ozn019zrlH2vtl8WqgbgJ4+NgL+0
NHCRveZMkvppwJPv6wIDAQAB
-----END PUBLIC KEY----
""")


LOG_FILENAME = '/u/debit/logs/VPGsvr.log'

def slog(msg, loglevel=logging.DEBUG):
    logging.basicConfig(filename=LOG_FILENAME,level=loglevel)
    newmsg=timeNow()+msg
    logging.debug(newmsg)

def readDB(k1, k2):
   import sqlite3
   try:
      conn = sqlite3.connect('/u/debit/apps/htdocs/QAdata/db/QAImtu.db')
      c = conn.cursor()
      stmt="select xml from xmlData where app='%s' and key='%s'" % (k1, k2) 
      #slog ("INFO: readDB stmt %s" % stmt)
      c.execute(stmt)
      d=c.fetchone()
      if d == None:
         return None
      slog ("INFO: readDB from DB <%s>" % str(d[0]))
      return str(d[0])
   except sqlite3.Error as e:
      slog ("ERROR: retProcInstr sqlite3.connect %s" % e.args[0])
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

class NatcomIso8583(ISO8583):
  """change iso to VPG spec"""

  def __init__(self):
     ISO8583.__init__(self, iso="", debug=False)
     #obj.redefineBit(self, bit, smallStr, largeStr, bitType, size, valueType)
     ISO8583.redefineBit(self, 7, '7', 'Timestamp', 'N', 14, 'n')
     ISO8583.redefineBit(self, 11, '11', 'TransactionID', 'N', 15, 'an')
     ISO8583.redefineBit(self, 48, '48', 'query', 'LLL', 999, 'ansb')
     ISO8583.redefineBit(self, 63, '63', 'PartnerID', 'LL', 99, 'an')
     #self.redefineBit(64, '64', 'Signature', 'LLL', 999, 'an')
     ISO8583._BITS_VALUE_TYPE[64] = ['64', 'Signature', 'LLL', 999, 'ansb']

  def parseObj(self):
      slog ("The MTI is: %s" % self.getMTI())
      msgtxt=''
      v1 = self.getBitsAndValues()
      for v in v1:
         value=v['value']
         slog ('Bit %s of type %s with value = %s' % (v['bit'],v['type'],value))
         if v['bit'] in ('2', '3', '4', '7', '11', '23', '39', '63'):
             if v['type'] == 'LL':
                value = value[2:]
             elif v['type'] == 'LLL':
                value = value[3:]
             msgtxt +=value

      return msgtxt


def verifySignature(textstr, sig):
    sgNo64=sig.decode('base64')
    #key=open('/u/debit/apps/htdocs/VPG/IDT_public_key_1024.pem').read()
    rsaO=RSA.importKey(PUBLIC_KEY)
    h = SHA.new(textstr)
    verifier = PKCS1_v1_5.new(rsaO)
    return verifier.verify(h, sgNo64)

def signSignature(textstr):
    #key=open('/u/debit/apps/htdocs/VPG/IDT_private_key_1024.pem').read()
    rsaO=RSA.importKey(PRIVATE_PEM)
    h = SHA.new(textstr)

    signer = PKCS1_v1_5.new(rsaO)
    signature = signer.sign(h)
    sg64=signature.encode('base64')

    #slog('signSignature len<%d> : %s' % ( len(sg64), sg64))

    return sg64

def timeNow():
    format="%b %d %H:%M:%S "
    today = datetime.datetime.today()
    return today.strftime(format)

def ckStdin():
    from select import select
    timeout = 1
    rlist, _, _ = select([sys.stdin], [], [], timeout)
    if rlist:
       return True
    else:
       slog ('ckStdin found no data. Exiting!!')
       return False


def getdata():
    #slog('getdata starting')
    if ckStdin() == False:
       sys.exit(0)
    data=""
    line=sys.stdin.read(4)
    l=line
    try: 
       sz=int(line)
    except ValueError, ii:
             sys.exit(1)
    #slog('getdata sz<%d> bytes' % sz)
    line = sys.stdin.read(sz)
    #slog('getdata data<%s> bytes' % line)
    data += line
    #reqm= l + data 
    #open('/u/debit/apps/htdocs/VPG/requestMsg', 'w').write(reqm)
    return data

def sendResponse(m):
    ans = m.getRawIso()
    sz= "%04d" % len(ans)
    ans=sz+ans
    #slog ('Sending back len<%s> msg: %s' % (sz, ans))
    sys.stdout.write(ans)
    sys.stdout.flush()

def delayRsp(delay, abort):
    if delay > 0:
       slog ('Received instruction to delay rsp <%d> seconds.' % delay)
       sleep(delay)
    if abort == True:
       slog ('Received instruction abort <%s>' % abort)
       sys.exit(0)


def handletopup(m):
    slog ('handletopup starting')
    msisdn=m.getBit(2)
    amount=m.getBit(3)
    date=m.getBit(7)
    tid=m.getBit(11)
    partnerID=m.getBit(63)
    signature=m.getBit(64)
    msgtxt=m.parseObj()

    #open('/u/debit/apps/htdocs/VPG/RQkeytxt', 'w').write(msgtxt)
    #open('/u/debit/apps/htdocs/VPG/RQsignature', 'w').write(signature[3:])

    isValid=verifySignature(msgtxt, signature[3:])

    #slog ('handletopup - req - msgtxt : <%s> isValid=<%s>' % (msgtxt, isValid))

    retCode='00'
    delay=0
    abort=False
    intr=ckInstrFromDB('NATCOM_VPG', msisdn)
    if intr != None:
       try:
         retCode=intr['a0'] 
         delay=int(intr['a1']) 
         abort=bool(intr['a2']=='True') 
       except KeyError:
         pass

    delayRsp(delay, abort)
    #send answer
    if isValid == False:
       retCode='83'
    m.setMTI('0210')
    m.setBit(3, '000000')
    # Response Code for error handling
    m.setBit(39, retCode)
			
    slog ('handletopup: has built topup response:')
    msgtxt=m.parseObj()
    #slog ('handletopup - msgtxt : %s' % msgtxt)

    sig= signSignature(msgtxt)
    #slog ('handletopup - resp: signature (size %d) : %s' % (len(sig), sig))
    m.setBit(64, sig)
    sendResponse(m)

def handleping(m):
    slog ('handleping request')
    msgtxt=m.parseObj()
    m.setMTI('0810')
    m.setBit(39, '00')
    sendResponse(m)

def handlestatus(m):
    slog ('handlestatus query')
    msgtxt=m.parseObj()
    m.setMTI('0211')
    m.setBit(39, '00')
    slog ('handlestatus: response:')
    msgtxt=m.parseObj()

    sig= signSignature(msgtxt)
    m.setBit(64, sig)
    sendResponse(m)

slog("VPGSvr starting - pid <%s>" % os.getpid())


bigEndian = True
#bigEndian = False


for i in (1,2):
   isoStr=getdata()
   slog ("Input <%d> Msg Len<%d> |%s|" % (i, len(isoStr), isoStr))
   natcomMsg = NatcomIso8583()

   #parse the iso
   try:
      natcomMsg.setIsoContent(isoStr)
      slog ("natcomMsg.getBitsAndValues setIsoContent\n")
      #natcomMsg.parseObj()

      mti=natcomMsg.getMTI()

      if mti == '0200':
         handletopup(natcomMsg)
      elif mti == '0201':
         handlestatus(natcomMsg)
      elif mti == '0800':
         handleping(natcomMsg)
      else:
         raise InvalidIso8583('This is not a valid natcom message!!')

   except InvalidIso8583, ii:
             slog("Received exception InvalidIso8583 %s" % ii)
   except SystemExit, ii:
             slog("SystemExit %s" % ii)
   except:
             slog ('Something happened!!!!')
