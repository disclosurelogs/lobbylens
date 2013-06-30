
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.SQLWarning;
import java.sql.Statement;

import java.io.File;
import java.io.IOException;
import java.io.InputStream;
import java.io.Writer;
import java.util.HashMap;
import java.util.Map;

import org.neo4j.graphdb.Direction;
import org.neo4j.graphdb.DynamicLabel;
import org.neo4j.graphdb.DynamicRelationshipType;
import org.neo4j.graphdb.GraphDatabaseService;
import org.neo4j.graphdb.Label;
import org.neo4j.graphdb.Node;
import org.neo4j.graphdb.RelationshipType;
import org.neo4j.helpers.collection.MapUtil;
import org.neo4j.unsafe.batchinsert.BatchInserter;
import org.neo4j.unsafe.batchinsert.BatchInserters;

public class Importer {

    public static void main(String[] argv) {
Map<String, String> config = new HashMap<String, String>();
config.put( "neostore.nodestore.db.mapped_memory", "90M" );
BatchInserter inserter = BatchInserters.inserter("target/batchinserter-example-config", config );
        //BatchInserterIndexProvider indexProvider = new LuceneBatchInserterIndexProvider(inserter);
        //BatchInserterIndex names = indexProvider.nodeIndex("names", MapUtil.stringMap("type", "exact"));
        //names.setCacheCapacity("name", 100000);



        System.out.println("-------- PostgreSQL "
                + "JDBC Connection Testing ------------");

        try {

            Class.forName("org.postgresql.Driver");

        } catch (ClassNotFoundException e) {

            System.out.println("Where is your PostgreSQL JDBC Driver? "
                    + "Include in your library path!");
            e.printStackTrace();

        }

        System.out.println("PostgreSQL JDBC Driver Registered!");

        Connection conn = null;

        try {

            conn = DriverManager.getConnection(
                    "jdbc:postgresql://127.0.0.1:5432/contractDashboard",
                    "postgres", "snmc");

        } catch (SQLException e) {

            System.out.println("Connection Failed! Check output console");
            e.printStackTrace();

        }

        if (conn != null) {
            System.out.println("You made it, take control your database now!");
        } else {
            System.out.println("Failed to make connection!");
        }
        try {
            // Print all warnings
            for (SQLWarning warn = conn.getWarnings(); warn != null; warn = warn.getNextWarning()) {
                System.out.println("SQL Warning:");
                System.out.println("State  : " + warn.getSQLState());
                System.out.println("Message: " + warn.getMessage());
                System.out.println("Error  : " + warn.getErrorCode());
            }

            // Get a statement from the connection
            Statement stmt = conn.createStatement();

            // Execute the query
            ResultSet rs = stmt.executeQuery("SELECT contractnotice.\"agencyName\", "
                    + " (case when \"supplierABN\" != 0 THEN \"supplierABN\"::text ELSE \"supplierName\" END) as supplierID , max(contractnotice.\"supplierName\") as \"supplierName\",sum(value) as sum "
                    + "FROM  public.contractnotice  GROUP BY contractnotice.\"agencyName\", "
                    + " (case when \"supplierABN\" != 0 THEN \"supplierABN\"::text ELSE \"supplierName\" END)");
            HashMap<String, Long> supplierIDs = new HashMap<String, Long>();
            HashMap<String, Long> agencyIDs = new HashMap<String, Long>();

Label agencyLabel = DynamicLabel.label( "Agency" );
inserter.createDeferredSchemaIndex( agencyLabel ).on( "name" );
Label supplierLabel = DynamicLabel.label( "Supplier" );
inserter.createDeferredSchemaIndex( agencyLabel ).on( "name" );

            // Loop through the result set
            while (rs.next()) {
                long supplierID, agencyID;
                String supplierKey;
                if (agencyIDs.get(rs.getString("agencyName")) == null) {
		    Map<String, Object> properties = new HashMap<String, Object>();
                    properties.put("name", rs.getString("agencyName"));
                    properties.put("type", rs.getString("agency"));
		    agencyID = inserter.createNode(properties, agencyLabel);
                    agencyIDs.put(rs.getString("agencyName"), agencyID);
                    if (agencyID % 10 == 0) {
                        System.out.println("Agency " + agencyID);
                    }
                }
                agencyID = agencyIDs.get(rs.getString("agencyName"));


                // inject some data 
                if (supplierIDs.get(rs.getString("supplierID")) == null) {
		    Map<String, Object> properties = new HashMap<String, Object>();
                    properties.put("name", rs.getString("supplierName"));
                    properties.put("type", rs.getString("supplier"));
		    supplierID = inserter.createNode(properties, supplierLabel);
                    supplierIDs.put(rs.getString("supplierID"), supplierID);
                    if (supplierID % 1000 == 0) {
                        System.out.println("Supplier " + supplierID);
                    }
                }
                supplierID = supplierIDs.get(rs.getString("supplierID"));


// To set properties on the relationship, use a properties map
// instead of null as the last parameter.
Map<String, Object> properties = new HashMap<String, Object>();
properties.put( "value", rs.getDouble("sum"));
                inserter.createRelationship(agencyID, supplierID,
                        DynamicRelationshipType.withName("PAYS"), properties);
                inserter.createRelationship(supplierID, agencyID,
                        DynamicRelationshipType.withName("PAID_BY"), properties);
            }
            // Close the result set, statement and the connection
            rs.close();
            stmt.close();
            conn.close();
        } catch (SQLException se) {
            System.out.println("SQL Exception:");

            // Loop through the SQL Exceptions
            while (se != null) {
                System.out.println("State  : " + se.getSQLState());
                System.out.println("Message: " + se.getMessage());
                System.out.println("Error  : " + se.getErrorCode());

                se = se.getNextException();
            }
        }
//make the changes visible for reading, use this sparsely, requires IO!
//        names.flush();

// Make sure to shut down the index provider
//        indexProvider.shutdown();
        inserter.shutdown();
    }
}
