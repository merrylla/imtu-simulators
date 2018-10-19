import logging
import sys
import select
import datetime

LOG_FILENAME = '/u/debit/logs/VPGsvr.log'

def decodeXml(x):
    if x == None:
       return None
    import xml.etree.ElementTree as ET
    root=ET.fromstring(x)
    parmList={}
    for child in root:
        parmList[child.tag]= child.text

    if (len(parmList) == 0):
       return None
    return parmList

x='<qryparams><a0>44</a0></qryparams>'
d=decodeXml(x)
print d
