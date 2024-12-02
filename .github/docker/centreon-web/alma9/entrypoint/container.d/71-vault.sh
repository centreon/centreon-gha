#!/bin/sh

# while [ ! -f "/tmp/shared-volume/vault-ids" ]; do
#   sleep 5
#   ls -lah /tmp/shared-volume
# done

# . /tmp/shared-volume/vault-ids

if [ ! -z ${VAULT_HOST} ] && getent hosts ${VAULT_HOST}; then
  sed -i 's/default_options:/&\n            verify_host: true/' /usr/share/centreon/config/packages/framework.yaml
  sed -i 's/default_options:/&\n            verify_peer: true/' /usr/share/centreon/config/packages/framework.yaml

  RESPONSE=$(curl -s -w "%{http_code}" -H 'Content-Type:application/json' -H 'Accept:application/json' -d '{"security":{"credentials":{"login":"admin","password":"Centreon!2021"}}}' -L "http://localhost:80/centreon/api/latest/login")
  TOKEN=$(echo "$RESPONSE" | head -c -4 | jq -r '.security.token')

  RESPONSE=$(curl -X PUT \
      -H "Content-Type: application/json" \
      -H "X-AUTH-TOKEN: $TOKEN" \
      -L "http://localhost:80/centreon/api/latest/administration/vaults/configurations" \
      --data '{"address": "vault", "port": 8200, "root_path": "centreon/", "role_id": "'"$VAULT_ROLE_ID"'", "secret_id": "'"$VAULT_SECRET_ID"'"}')

  STATUS=$(echo "$RESPONSE" | tr -d '\n' | tail -c 3)

  if [[ $STATUS -eq 200 ]]; then
    sudo -u apache php /usr/share/centreon/bin/console credentials:migrate-vault
  fi
fi
