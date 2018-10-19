LOG_FILENAME = '/u/debit/logs/TelcelSim.log'

from imtuWSlib import *
from struct import *
from random import *
import os
import sys

import time
import pickle

#from threading import Timer

DELIMETER='\xb6'
recharge='10'
cancellation='21'
enquiry='30'

def CSQsendRsp(opNum, fields):
    msg='CSQ01R00'+opNum+DELIMETER
    for i in fields:
       msg= msg+ i +':'+ fields[i]+DELIMETER
    msg=msg+'.'
    slog ('Sending msg to remote - len<%s> msg: %s' % (len(msg), msg))
    try:
       if sys.stdout.write(msg) == 0:
          raise SystemExit
       sys.stdout.flush()
    except:
       raise SystemExit('Sent CSQsendRsp failed - connection might have been lost.' )

def buildRsp(code, msg, oFields={}):
    ok='KO'
    if code in ('10', '20', '23', '30', '92'):
       ok='OK'
    r={'22':code, '23':ok, '50':msg}
    r.update(oFields)
    return r

class CSQheader(object):
    protID = 'CSQ01'
    type = 'S' #S app, R resp, O ok/confirmation
    encrypt = '00' #Encription type 00: no encription
    opNum = '' #tid
    loginId = ''
    state='init'   #init,topuped, topupError, pending, canceled, topupConf 
    fsCode='10'
    nxCode='10'
    time0=0
    dur=0
    savFlag=False

    def __init__(self, h):
      #slog ('CSQheader init: %s' % h)
      type=h[5]
      if self.state in ('init') and type == 'O':
          raise Exception('ERROR: interface - received confirmation at init state')
      elif type == 'R':
          raise Exception('ERROR: interface - client cannot send Response Msg')
      else:
        self.type = type
        self.protID=h[:5]
        self.type=h[5]
        self.encrypt=h[6:8]
        self.loginId=h[8:] 

    def isExpired(self):
       return time.time() - self.time0 > self.dur

class CSQmsg(CSQheader, Exception):
    """Class object to encode/decode CSQ msg"""  
    msgF={}
    conFmsg=[]
    msisdn=''

    def __init__(self, msg):
      self.setup(msg)
 
    def setup(self, msg):
      msgA=msg.rsplit(DELIMETER)
      msgA.pop()
      CSQheader.__init__(self, msgA.pop(0))
      if self.type == 'O':
         self.conFmsg=msgA
      else: #topup, enquiry, cancel
        for i in msgA:
          f=i.rsplit(':')
          self.msgF.update({f[0] : f[1]})
        if self.state == 'init':
          self.opNum=self.msgF['6'] 
#         if self.msgF['11'] in (enquiry, cancellation):
# NOTE: make DB entry NOT mandatory for cancellation  - cfw
          if self.msgF['11'] == enquiry:
             tc=getContent(self.msgF['17'])
             if tc == None:
                self.handleErr('27', 'Transaction not found in DB')
             else:
                self.state=tc['state']
                self.fsCode=tc['fsCode']
                self.nxCode=tc['nxCode']
                self.time0=tc['time0']
                self.dur=tc['dur']
                self.opNum=tc['opNum']
                self.msisdn=tc['msisdn']
                self.savFlag=tc['savFlag']

    def enqValidation(self):
        pass

    def appValidation(self):

        if CSQheader.type != 'S':
           self.handleErr('95', 'incorrect application type <%s>' % CSQheader.type)   
        try:
           self.msgF['2']
           self.msgF['6']
           self.msgF['7']
           self.msgF['8']
           self.msgF['11']
           self.msgF['13']
           self.msgF['14']
           self.msisdn=self.msgF['12'] 
        except KeyError as e:
           msg='Request msg has missing field <%s>' % e
           self.handleErr('971', msg)
        else:
           #need code to ck uniqueness of oprNum 6 within the same day
           #need date 7, and time 8 validation
           pass
       

    def conFvalidation(self):
        #slog ("CSQmsg conFvalidation")
        if self.conFmsg[0] != self.msgF['22']:
           msg='Error conFvalidation: result code <%s> vs <%s> is mismatch' % (self.conFmsg[0],self.msgF[22]) 
           #self.handleErr('96', msg)
           raise Exception(msg)

    def cancelValidation(self):
        try:
           self.msgF['2']
           self.msgF['6']
           self.msgF['7']
           self.msgF['8']
           self.msgF['11']
           self.msgF['17']
           self.msgF['18']

        except KeyError as e:
           msg='Error: cancelValidation field<%s> missing' % e
           self.handleErr('96', msg)
        #else:
        #   return ckCancel3756815075(self)


    def handleErr(self, code, msg):
        if '6' not in self.msgF:
           self.msgF['6']='00000'
        opNum=self.msgF['6']
        m=buildRsp(code, msg)
        self.msgF['22']=code
        CSQsendRsp(opNum, m)

def ckCancel3756815075(p):
    
    if p.state == 'init':
       p.state = 'canceled'
       p.fsCode='20'
       p.nxCode='20'
       p.time0=time.time()
    if p.msisdn == '3756815075':
       p.state = 'pending'
       p.fsCode='25'
       p.dur=7
       return p.fsCode, 'pending'

    if p.nxCode =='10':
       p.nxCode = '20'

    return p.fsCode, 'Cancellation received'

def savContent(p):
    p.savFlag = True
    t={'state': p.state, 'fsCode': p.fsCode, 'nxCode': p.nxCode, 'time0':p.time0, 'dur': p.dur, 'opNum': p.opNum, 'msisdn': p.msisdn, 'savFlag': p.savFlag} 
    tc=pickle.dumps(t)
    store2DB('CSQ.state', p.opNum, tc)

def getContent(key):
    x=readDB('CSQ.state', key)
    if x == None:
       return None 
    else:
       return pickle.loads(x)
    

def getExtInstrDB(app, key, p):
    retCode='10'
    retMsg='Operation-completed correctly'
    delay=0
    abort=False
    login='a0'
    pw='a1'
    amount='a2'
    rc='a3'
    rm='a4'
    prodid='a5'
    rc2='a6'
    retDur='a7'
    intr=ckInstrFromDB(app, key)
    if intr != None:
       retCode, retMsg = intr[rc], intr[rm]
       if intr[login] != p.loginId or intr[pw] !=p.msgF['2']:
           retCode, retMsg='905', 'Config Authorization failed'
       elif intr[amount] != p.msgF['14']:
           retCode, retMsg='927', 'invalid topup amount'
       elif intr[prodid] != p.msgF['13']:
           retCode, retMsg='29', 'invalid product'

       p.fsCode = retCode
       p.nxCode = retCode
       try:
          p.nxCode= intr[rc2]
          p.dur = int(intr[retDur])
       except KeyError:
          pass
       
       delay=int(intr['delay'])
       abort=bool(intr['abort']=='true')

    p.msgF['22']=retCode
    p.state = 'topuped'
    if retCode in ('25', '35'):
       p.state = 'pending'
    elif retCode not in ('10', '20', '23', '30', '92'):
       p.state = 'topupError'

    if (delay > 10 or abort == True):
        savContent(p)
     
    delayRsp(delay, abort)
    return retCode,retMsg

def handleTopup(p):
    slog ("CSQsim handleTopup")
    p.appValidation()
    p.time0=time.time()
    rc,rm=getExtInstrDB('CSQ', p.msisdn, p)
    rsp=buildRsp(rc, rm, {'24': str(randrange(100000,999999))}) 
    CSQsendRsp(p.opNum, rsp)

def handleEnquiry(p):
    slog("CSQsim handleEnquiry")
    p.enqValidation()
    rc=p.fsCode
    if p.isExpired():
       rc=p.nxCode
    rsp=buildRsp(rc, 'Enquiry OK')
    p.msgF['22']=rc
    if rc in ('10', '20', '23', '30', '92'):
       p.state='topuped'
    elif rc not in ('25', '35'):
       p.state='topupError' 

    CSQsendRsp(p.opNum, rsp)
    
def handleCancellation(p):
    slog ("CSQsim handleCancellation")
    p.cancelValidation()
    #if p.nxCode =='10':
    #   p.nxCode = '20'
    rc,rm = p.fsCode, 'Cancellation received'
    if p.isExpired():
       rc='20'
       p.state='canceled'
    m=buildRsp(rc, rm)
    p.msgF['22']=rc
    CSQsendRsp(p.opNum, m)


def handleConfirmation(p):
    slog ("CSQsim handleConfirmation")
    p.conFvalidation()
    msg="<Result><status>0</status><message>%s</message></Result>" % p.state
    store2DB('CSQ.state', p.msisdn, msg)
    if p.savFlag == True:  
       delDB('CSQ.state', p.opNum)

pid=os.getpid()
slog ("CSQsim starting - pid <%s>" % pid)

EofChar=DELIMETER+'.'

try:
   while True:
      data=getdataD(EofChar, 300)
      #data=open('/debit/apps/htdocs/CSQ.topUpRequest').read()
      slog ("Input Msg Len<%d> <%s>" % (len(data), data))
      #open('/debit/apps/htdocs/CSQ/topUpRequest', 'w').write(data)


      try:
         csq.setup(data)
      except NameError:
         csq=CSQmsg(data)

      #slog ("MAIN: CSQ state <%s>" % (csq.state))

      if (csq.type == 'O'):
         action='Confirmation' 
      else:
         action = csq.msgF['11']


      if action == 'Confirmation':
         handleConfirmation(csq)

      elif action == recharge:
         handleTopup(csq)
      elif action == cancellation:
         handleCancellation(csq)
      elif action == enquiry:
         handleEnquiry(csq)
      else:
         msg='Invalide application type <%s>' % action
         csq.handleErr('99998', msg) 
         break


except IOError, ii:
    slog("IOError %s" % ii)

except SystemExit, ii:
    slog("SystemExit %s" % ii)

except Exception as e:
    slog("Caught an Exception <%s>" % e)

slog ("CSQ is exiting pid<%s>" % pid)
