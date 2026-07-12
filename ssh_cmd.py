import paramiko
import sys

with open(r'C:\Users\HP\Laravel\Sistem-Klasifikasi-Bantuan-Sosial-Desa-Pangalasiang\pwsupersecretcpanel.txt', 'r') as f:
    lines = f.read().strip().split('\n')
    username = password = None
    port = 22
    for line in lines:
        line = line.strip()
        if line.startswith('username:'):
            username = line.split(':', 1)[1].strip()
        elif line.startswith('password:'):
            password = line.split(':', 1)[1].strip()
        elif 'port ssh' in line.lower():
            port = int(line.split(':')[1].strip())

def ssh_exec(cmd):
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect('195.88.211.25', port=port, username=username, password=password, timeout=15)
    stdin, stdout, stderr = client.exec_command(cmd)
    out = stdout.read().decode()
    err = stderr.read().decode()
    client.close()
    return out, err

# Get command from argument
cmd = ' '.join(sys.argv[1:]) if len(sys.argv) > 1 else 'echo hello'
out, err = ssh_exec(cmd)
if out: print(out)
if err: print("STDERR:", err)
