
from Crypto.Signature import PKCS1_v1_5
from Crypto.Hash import SHA
from Crypto.PublicKey import RSA

message = open('./signingText').read()
key = RSA.importKey(open('private.pem').read())
h = SHA.new(message)
signer = PKCS1_v1_5.new(key)
signature = signer.sign(h)

open('./pycryptoSignature', 'w').write(signature)

key = RSA.importKey(open('public.pem').read())
h = SHA.new(message)
verifier = PKCS1_v1_5.new(key)
if verifier.verify(h, signature):
    print "The signature is authentic."
else:
    print "The signature is not authentic."
