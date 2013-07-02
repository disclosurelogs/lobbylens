rm -rfv target/batchinserter-example-config/*
mvn compile exec:java
mkdir ~/Downloads/neo4j-community-2.0.0-M03/data/graph.db
cp -rv target/batchinserter-example-config/* ~/Downloads/neo4j-community-2.0.0-M03/data/graph.db/

