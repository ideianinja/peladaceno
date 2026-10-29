"""Teste destrutivo: somente servidor e banco isolados. TEST_INSTALL_KEY obrigatório."""
import http.cookiejar, os, re, secrets, sys, urllib.error, urllib.parse, urllib.request
base=sys.argv[1]
client=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
def request(data=None):
    try:
        r=client.open(base,None if data is None else urllib.parse.urlencode(data).encode())
        return r.status,r.read().decode()
    except urllib.error.HTTPError as ex:
        return ex.code,ex.read().decode()
status,html=request()
assert status==200 and 'Vamos começar' in html
csrf=re.search(r'name="csrf" value="([^"]+)"',html)[1]
password=secrets.token_urlsafe(20)
data=dict(csrf=csrf,key='incorrect',email='setup@example.test',password=password,confirmation=password)
assert request(data)[0]==403
data['key']=os.environ['TEST_INSTALL_KEY']; data['csrf']='invalid'
assert 'Atualize a página' in request(data)[1]
data['csrf']=csrf; data['confirmation']='different'
assert 'não coincidem' in request(data)[1]
data['confirmation']=password
assert 'Tudo pronto!' in request(data)[1]
assert request()[0]==403
assert request(data)[0]==403
print('PASS: chave, CSRF, confirmação de senha, instalação e bloqueio de reinstalação')
