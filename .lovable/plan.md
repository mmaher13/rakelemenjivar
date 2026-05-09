The deployed code in this workspace already contains the fix to create `/var/www/rakelemenjivar.com/logs/contact.log`. If that file is missing on the server after deploy, the server likely ran an older `scripts/deploy.sh` that does not include the new log-creation step, or the latest repo changes were not pulled onto that server copy.

Plan:

1. Verify the server script version
   - On the server, check whether the deploy script contains the persistent log step:

```bash
grep -n "Persistent log dir\|contact.log\|REPO_DIR}/logs" /var/www/rakelemenjivar.com/scripts/deploy.sh
```

2. Create the log file immediately so contact logging works now

```bash
sudo mkdir -p /var/www/rakelemenjivar.com/logs
sudo touch /var/www/rakelemenjivar.com/logs/contact.log
sudo chown -R www-data:www-data /var/www/rakelemenjivar.com/logs
sudo chmod 755 /var/www/rakelemenjivar.com/logs
sudo chmod 644 /var/www/rakelemenjivar.com/logs/contact.log
sudo tail -f /var/www/rakelemenjivar.com/logs/contact.log
```

3. If the grep in step 1 shows nothing, update the server copy
   - Make sure the latest code is actually in GitHub and pulled by the server.
   - Then rerun:

```bash
cd /var/www/rakelemenjivar.com
bash scripts/deploy.sh
```

4. Optional hardening I can apply next
   - Move the log creation earlier in `scripts/deploy.sh` so it is created even if build/staging fails.
   - Update the final deploy output to print the correct tail command.
   - Update the old PHP comments that still mention `dist/api/contact.log`, so future troubleshooting points to the persistent log path only.