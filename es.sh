curl -X PUT "localhost:9200/messages/_settings" -H 'Content-Type: application/json' -d'
{
    "index" : {
	"blocks" : {
             "read_only_allow_delete": "false"
        }
    }
}'

curl -X PUT "localhost:9200/users/_settings" -H 'Content-Type: application/json' -d'
{
    "index" : {
	"blocks" : {
             "read_only_allow_delete": "false"
        }
    }
}'
