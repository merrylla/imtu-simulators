import commands

privateKey='/u/debit/apps/htdocs/VPG/private.pem '
signingText='/u/debit/apps/htdocs/VPG/signingText.tmp'

txt=open('/u/debit/apps/htdocs/VPG/signingText').read()
f=open(signingText, 'w')
f.write(txt)
f.close()

cmd='openssl dgst -sha1 -sign '+privateKey+signingText

signature=commands.getstatusoutput('openssl dgst -sha1 -sign private.pem signingText')

print signature[1].encode('base64')
