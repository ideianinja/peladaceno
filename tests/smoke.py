import http.cookiejar, os, re, sys, urllib.parse, urllib.request
base=sys.argv[1].rstrip('/')+'/'
jar=http.cookiejar.CookieJar()
client=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
def get(path=''):
    return client.open(base+path).read().decode()
def post(path, **data):
    html=get(path)
    data['csrf']=re.search(r'name="csrf" value="([^"]+)"',html)[1]
    return client.open(base+path,urllib.parse.urlencode(data).encode()).read().decode()
assert 'Bom ter você' in get()
html=post('',action='login',email='admin@example.test',password=os.environ['TEST_ADMIN_PASSWORD'])
assert 'Deixe a bola rolar' in html
html=post('',action='player',name='Teste <script>alert(1)</script>',phone='11999999999')
assert '&lt;script&gt;' in html and '<script>alert' not in html
html=post('',action='game',title='Pelada de teste',location='Arena de teste',starts_at='2030-12-01T19:00')
assert 'Pelada de teste' in html
invite=re.search(r'href="\?convite=([a-f0-9]+)"',html)[1]
post('',action='logout')
assert 'Bom ter você' in get()
path='?convite='+invite
html=get(path)
assert '11999999999' not in html
player=re.search(r'<option value="(\d+)"',html)[1]
assert 'Presença atualizada!' in post(path,action='attendance',player=player,status='yes')
assert 'Presença atualizada!' in post(path,action='attendance',player=player,status='no')
html=post('',action='login',email='admin@example.test',password=os.environ['TEST_ADMIN_PASSWORD'])
assert 'Não vai' in html and '0 confirmados' in html
try:
    client.open(base,urllib.parse.urlencode({'action':'player','name':'CSRF'}).encode())
except urllib.error.HTTPError:
    pass
assert 'CSRF' not in get()
print('PASS: login, logout, cadastro, escape HTML, convite, privacidade, confirmação, atualização e proteção CSRF')
