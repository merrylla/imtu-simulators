#!/usr/local/bin/python
"""
VPG_NETCOM end point simulator
"""
import warnings

with warnings.catch_warnings():
    #warnings.filterwarnings("ignore",category=PowmInsecureWarning)
    warnings.filterwarnings("ignore",category=RuntimeWarning)
    from Crypto.PublicKey import RSA

import logging
import sys
import select
import datetime

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
MIICXgIBAAKBgQCwybYgPVYfr2VTrhKGn+5CGj0SpbKwLYx4R6jUHdw5d6LCr1Um
VGFl5bP5tZyDpVvARWv9na3OtFXdym/DouJQIBlUQhBuURdbZMhGKKujegmwvP7p
wxwYvgI813moRjjVB45+HQQ6+jLothTCsWYd23xQi5H/aC8QV7VLqpqfyQIDAQAB
AoGBAJpN1StanftV6gkZ3I2othr4L+pAujBP8VVippdN4NQ/+c4Xnyivabu4vFfu
DkhRYj312gYpRHUwrenCMb7+QqzbtzZb7gZywCxmrfq5euKrcdQKWxN2L2AevOOc
VxQ41ew/ufvZB5rfg4vzuaDESAh4ch0HzqnH/Va3G7AHIeFJAkEA60BtZSgqdF9Q
lrf8ZqXd/is83rCqlQQrGt+AgmAt+C1X7gEUsRGNr3hYk+cLXKIoxrKWn5J5yDnc
DyI9a7NJ+wJBAMBhRl8BJ8e+m11M8ONyQzs05GVuDvvQPY7QILWcupSaVmqSywir
OT/jNfCxHFl6mkqvzF6j+caOrem9VXaKtgsCQEwHGlOi03WgiC7tbwjNehz2ZEmj
1r0qB7Q7nxCDfNfD77Lfbox7G8slnZrPBID/dyYf+UXA1NK8wD2z1x3DZ5kCQQCL
vp2VqYsyB31oOtnI/llkCrnWDCqKNOxib0Eza5QFGk+nvtYJiAdgJzYpjx4eyXaG
xkjYfpiT6VTfs47/OyedAkEAii8vTebWQwYFjfWwvA4JJldCDhUAZ1jkCuxnsZUF
WfsrEygzCqXRv35fAuQX5NBR9Hr+iI0jqUU5Z6qKDejTRA==
-----END RSA PRIVATE KEY-----
""")

PUBLIC_KEY = b("""-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCwybYgPVYfr2VTrhKGn+5CGj0S
pbKwLYx4R6jUHdw5d6LCr1UmVGFl5bP5tZyDpVvARWv9na3OtFXdym/DouJQIBlU
QhBuURdbZMhGKKujegmwvP7pwxwYvgI813moRjjVB45+HQQ6+jLothTCsWYd23xQ
i5H/aC8QV7VLqpqfyQIDAQAB
-----END PUBLIC KEY----
""")


LOG_FILENAME = '/u/debit/logs/VPGsvr.log'


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
    #slog('verifySignature - decoded sig size <%d>: ' % len(sgNo64))
    key=open('/u/debit/apps/htdocs/VPG/IDT_public_key_1024.pem').read()
    rsaO=RSA.importKey(key)
    h = SHA.new(textstr)
    verifier = PKCS1_v1_5.new(rsaO)
    return verifier.verify(h, sgNo64)

def signSignature(textstr):
    key=open('/u/debit/apps/htdocs/VPG/IDT_private_key_1024.pem').read()
    rsaO=RSA.importKey(key)
    #rsaO=RSA.importKey(PRIVATE_PEM)
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

def slog(msg, loglevel=logging.DEBUG):
    logging.basicConfig(filename=LOG_FILENAME,level=loglevel)
    newmsg=timeNow()+msg
    logging.debug(newmsg)

def getdata():
    data=""
    line=sys.stdin.read(4)
    l=line
    sz=int(line)
    line = sys.stdin.read(sz)
    data += line
    reqm= l + data 
    #open('/u/debit/apps/htdocs/VPG/requestMsg', 'w').write(reqm)
    return data

def sendResponse(m):
    ans = m.getRawIso()
    sz= "%04d" % len(ans)
    ans=sz+ans
    #slog ('Sending back len<%s> msg: %s' % (sz, ans))
    sys.stdout.write(ans)
    sys.stdout.flush()

def abort(delay):
    if delay < 0:
       slog ('Received instruction <%d> to abort the transaction.' % delay)
       sys.exit(1)


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

    #send answer
    retCode='00'
    delay=0
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
    abort(delay)
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
    msgtxt=m.parseObj()
    slog ('handletopup - msgtxt : %s' % msgtxt)

    sig= signSignature(msgtxt)
    slog ('handletopup - resp: signature (size %d) : %s' % (len(sig), sig))
    m.setBit(64, sig)
    sendResponse(m)

slog("VPGSvr starting")

bigEndian = True
#bigEndian = False

isoStr=getdata()

if isoStr:
   #slog ("Input Msg Len<%d> |%s|" % (len(isoStr), isoStr))
   #pack = ISO8583()
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
   except:
             slog ('Something happened!!!!')
