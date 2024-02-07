# gsu-d2l/datahub-extract-cli

## Requirements
- Some flavor of Linux (Debian or Red-Had based). This tool could _probably_ be run from Windows, but that has never been tested.
- PHP (>=8.2) with the following extensions:
  - curl
  - xml
  - zip
- Composer
- MySQL client and/or Oracle Instant Client

## Setup
Download from GitHub and install:
```sh
wget https://github.com/gsu-d2l/datahub-extract-cli/archive/refs/tags/v1.0.0.zip
unzip datahub-extract-cli-1.0.0.zip
cd datahub-extract-cli-1.0.0
composer update
```

Create `.env` and `bin/.env` files with the values for your specific environment. See the cooresponding `.env.example` files for what should be there.
```sh
cp .env.example .env
vim .env

cp bin/.env.example bin/.env
vim bin/.env
```

Create a `work/datasets/datasets.txt` file to specify what datasets to process. See `work/datasets.example.txt` for an example and `work/datasets.all.txt` for the full list.
```sh
cp work/datasets/datasets.all.txt work/datasets/datasets.txt
vim work/datasets.txt
```

Database schema for MySQL and Oracle can be found in `vendor/gsu-d2l/datahub-schema/schema/sql/`.

Documentation on database schema and the D2L Data Hub can be found [here](https://documentation.brightspace.com/EN/insights/data_hub/admin/bds_intro.htm).

## Getting Started

Run `${APP_DIR}/bin/datahub-extract-cli --help` to see full list of available commands.

#### Notes
- `${APP_DIR}` refers to the base path where `gsu-d2l/dataset-extract-cli` is installed.
- `${APP_ENV}` refers to the environment name (e.g., `oracle` would load `.env.oracle`)
- `${CHECK_URL}` refers to [Healthchecks.io](https://healthchecks.io) Ping URL

### Initial run
```sh
nohup /usr/bin/env APP_ENV="${APP_ENV}" \
  ${APP_DIR}/bin/crons/run --force \
  < /dev/null \
  >> "${APP_DIR}/logs/init.log" 2>&1 &

tail -F "${APP_DIR}/logs/init.log"
```

### Cron job
```sh
/usr/bin/env APP_ENV="${APP_ENV}" \
  ${APP_DIR}/bin/crons/run "${CHECK_URL}" \
  >> "${APP_DIR}/logs/cron.$(date +%Y_%m_%d).log" 2>&1
```

## License
Datahub Extract CLI is [MIT Licensed](LICENSE)