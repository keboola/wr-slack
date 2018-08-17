# Slack Writer

[![Build Status](https://travis-ci.com/keboola/wr-slack.svg?branch=master)](https://travis-ci.com/keboola/wr-slack)

Keboola Connection Writer for [Slack](https://slack.com/)

# Usage

The following parameters are required:

- `#token` - Workspace token obtained when the application is installed
- `channel` - Channel to send the messages to, e.g. `#my-channel` or `@some-person`

At least one table has to be provided on input mapping and it must have one or two columns. If present, the second 
column is assumed to be JSON string with message attachments.

Sample configuration:

```json
{
	"storage": {
		"input": {
			"tables": [
				{
					"source": "in.c-main.messages",
					"destination": "messages.csv"
				}
			]
		}
	},
	"parameters": {
		"#token": "xoxa-00000000",
		"channel": "@some-person"
	}
}
``` 

Sample table:

```
"message"
"A message with a <http://connection.keboola.com/|link>"
"A Multiline
message with *bold text*"
```

Sample table with attachments:

```
"first_col"
"some message","[{""text"":""some text"",""actions"":[{""type"":""button"",""name"":""response"",""value"":""yes"",""text"":""yes"",""style"":""primary""}]}]"
```


## Development
 
Clone this repository and init the workspace with following command:

```
git clone https://github.com/keboola/wr-slack
cd my-component
docker-compose build
docker-compose run --rm dev composer install --no-scripts
```

Run the test suite using this command:

```
docker-compose run --rm dev composer tests
```
 
# Integration

For information about deployment and integration with KBC, please refer to the [deployment section of developers documentation](https://developers.keboola.com/extend/component/deployment/) 
