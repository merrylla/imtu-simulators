#!/usr/local/bin/python
"""
Driver program to produce SH1 signature
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
key = RSA.importKey(open('private.pem').read())
h = SHA.new()
h.update(message)
signer = PKCS1_PSS.new(key)
signature = signer.sign(h)

open('./pysig', 'w').write(signature)

