VERSION=8.4.3
ES_HOME=c:/software/elasticsearch-$(VERSION)
KIB_HOME=c:/software/kibana-$(VERSION)

SERVER=http://localhost:9200
USER=--user elastic:VLtTXkTo

install:
	unzip elasticsearch-$(VERSION)-windows-x86_64.zip
	unzip kibana-$(VERSION)-windows-x86_64.zip
	composer require elasticsearch/elasticsearch

start-ES_KIB:
	cd $(ES_HOME)/bin && ./elasticsearch.bat &
	cd $(KIB_HOME)/bin && ./kibana.bat &


bulk_load: tmdb-bulk.json
	-curl.exe $(USER) -XDELETE $(SERVER)/tmdb
	echo
	curl $(USER) -f  -H 'Content-Type: application/json' \
             -XPUT $(SERVER)/tmdb -d '  { "settings": { "number_of_shards" : "1", "number_of_replicas" : "0" } }'
	curl $(USER) -f -X PUT $(SERVER)/tmdb/_bulk -H"Content-type: application/json" --data-binary @tmdb-bulk.json

clean:
	rm -f *.bak *~ web/*~ web/*.bak

