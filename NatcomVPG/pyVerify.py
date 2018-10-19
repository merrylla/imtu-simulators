#!/usr/local/bin/python
"""
Driver program to verify SH1 signature
"""
import warnings

with warnings.catch_warnings():
    #warnings.filterwarnings("ignore",category=PowmInsecureWarning)
    warnings.filterwarnings("ignore",category=RuntimeWarning)
    from Crypto.PublicKey import RSA

from Crypto.Signature import PKCS1_PSS
from Crypto.Hash import SHA
from Crypto.PublicKey import RSA

message=open('./signingText').read()
signature=open('./pysig').read()
key = RSA.importKey(open('public.pem').read())
h = SHA.new(message)
verifier = PKCS1_PSS.new(key)
if verifier.verify(h, signature):
    print "The signature is authentic."
else:
    print "The signature is not authentic."
