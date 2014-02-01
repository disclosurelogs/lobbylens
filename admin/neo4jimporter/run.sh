#http://docs.neo4j.org/chunked/stable/configuration-linux-notes.html
/etc/init.d/neo4j-service stop
rm -rfv target/batchinserter-example-config/*
#/var/lib/neo4j/data/graph.db/
mvn compile exec:java
#cp -rv target/batchinserter-example-config/* /var/lib/neo4j/data/graph.db/

/etc/init.d/neo4j-service start
