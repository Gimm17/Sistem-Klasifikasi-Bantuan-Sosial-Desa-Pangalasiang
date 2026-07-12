import paramiko
import sys
import os

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

# SFTP upload
local_file = sys.argv[1]
remote_file = sys.argv[2]

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('195.88.211.25', port=port, username=username, password=password, timeout=15)

sftp = client.open_sftp()
sftp.put(local_file, remote_file)
print(f"Uploaded: {local_file} -> {remote_file}")
sftp.close()
client.close()
