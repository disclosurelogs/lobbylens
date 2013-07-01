
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
        config.put("neostore.nodestore.db.mapped_memory", "90M");
        BatchInserter inserter = BatchInserters.inserter("target/batchinserter-example-config", config);
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

            HashMap<String, Long> supplierIDs = new HashMap<String, Long>();
            HashMap<String, Long> agencyIDs = new HashMap<String, Long>();
            HashMap<String, Long> lobbyingFirmIDs = new HashMap<String, Long>();
            HashMap<String, Long> lobbyistClientIDs = new HashMap<String, Long>();
            HashMap<String, Long> donorIDs = new HashMap<String, Long>();
            HashMap<String, Long> partyIDs = new HashMap<String, Long>();
            Label agencyLabel = DynamicLabel.label("Agency");
            inserter.createDeferredSchemaIndex(agencyLabel).on("name");
            Label supplierLabel = DynamicLabel.label("Supplier");
            inserter.createDeferredSchemaIndex(agencyLabel).on("name");
            Label donorLabel = DynamicLabel.label("Political Donor");
            inserter.createDeferredSchemaIndex(donorLabel).on("name");
            Label partyLabel = DynamicLabel.label("Political Party");
            inserter.createDeferredSchemaIndex(partyLabel).on("name");
            Label lobbyistClientLabel = DynamicLabel.label("Lobbyist Client");
            inserter.createDeferredSchemaIndex(lobbyistClientLabel).on("name");
            Label lobbyistLabel = DynamicLabel.label("Lobbyist");
            inserter.createDeferredSchemaIndex(lobbyistLabel).on("name");
            Label lobbyingFirmLabel = DynamicLabel.label("Lobbying Firm");
            inserter.createDeferredSchemaIndex(lobbyingFirmLabel).on("name");

            // TODO pregenerate suppliers and mark those that are donors/lobbyist clients

            ResultSet rs = stmt.executeQuery("SELECT min(\"supplierName\") as \"supplierName\",max(\"supplierABN\") as \"supplierABN\",\"lobbyistClientID\" from contractnotice inner join lobbyist_clients on  \"supplierABN\" = \"ABN\"  where \"supplierABN\" is not null group by \"lobbyistClientID\"");
            // TODO include alias lobbyist client names

           while (rs.next()) {
               long supplierID;
                Map<String, Object> properties = new HashMap<String, Object>();

                properties.put("name", rs.getString("supplierName"));
                properties.put("abn", rs.getString("supplierABN"));
                properties.put("supplier", "true");
               properties.put("lobbyistclient", "true");
                supplierID = inserter.createNode(properties, supplierLabel, lobbyistClientLabel); // http://api.neo4j.org/2.0.0-M03/org/neo4j/unsafe/batchinsert/BatchInserter.html#setNodeLabels(long, org.neo4j.graphdb.Label...)
                supplierIDs.put(rs.getString("supplierABN"), supplierID);
                lobbyistClientIDs.put(rs.getString("lobbyistClientID"), supplierID);
                if (supplierID % 1000 == 0) {
                    System.out.println("Supplier + lobbyist client" + supplierID);
                }
            }
            rs.close();

            //SELECT DISTINCT "supplierABN" from contractnotice,  (select max("DonorClientNm"),"RecipientClientNm",sum("AmountPaid") as "AmountPaid"
            //from political_donations group by "RecipientClientNm" order by "RecipientClientNm" desc) donors where "supplierABN" is not null limit 10


            // TODO pregenerate lobbying firms with their lobbyists and mark those firms that are donors


            // TODO pregenerate lobbyist clients that are donors

            //  agency/supplier relationships
            // TODO detect suppliers that are also agencies
            rs = stmt.executeQuery("SELECT contractnotice.\"agencyName\", "
                    + " (case when \"supplierABN\" != 0 THEN \"supplierABN\"::text ELSE \"supplierName\" END) as supplierID , max(contractnotice.\"supplierName\") as \"supplierName\",sum(value) as sum "
                    + "FROM  public.contractnotice  GROUP BY contractnotice.\"agencyName\", "
                    + " (case when \"supplierABN\" != 0 THEN \"supplierABN\"::text ELSE \"supplierName\" END)");


            // Loop through the result set
            while (rs.next()) {
                long supplierID, agencyID;
                String supplierKey;
                if (agencyIDs.get(rs.getString("agencyName")) == null) {
                    Map<String, Object> properties = new HashMap<String, Object>();
                    properties.put("name", rs.getString("agencyName"));
                    properties.put("agency", "true");
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
                    properties.put("supplier", "true");
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
                properties.put("value", rs.getDouble("sum"));
                inserter.createRelationship(agencyID, supplierID,
                        DynamicRelationshipType.withName("PAYS"), properties);
                inserter.createRelationship(supplierID, agencyID,
                        DynamicRelationshipType.withName("PAID_BY"), properties);
            }
            // Close the result set, statement and the connection
            rs.close();

            //  donor/donation recipient relationships
            /*'select "DonorClientNm",max("RecipientClientNm") as "RecipientClientNm",
            max("DonationDt") as "DonationDt", sum("AmountPaid") as "AmountPaid" from political_donations where
            "RecipientClientNm"
            LIKE ? group by "DonorClientNm" order by "DonorClientNm" desc '

            ResultSet rs = stmt.executeQuery("SELECT contractnotice.\"agencyName\", "
                    + " (case when \"supplierABN\" != 0 THEN \"supplierABN\"::text ELSE \"supplierName\" END) as supplierID , max(contractnotice.\"supplierName\") as \"supplierName\",sum(value) as sum "
                    + "FROM  public.contractnotice  GROUP BY contractnotice.\"agencyName\", "
                    + " (case when \"supplierABN\" != 0 THEN \"supplierABN\"::text ELSE \"supplierName\" END)");


            // Loop through the result set
            while (rs.next()) {
                long supplierID, agencyID;
                String supplierKey;
                if (agencyIDs.get(rs.getString("agencyName")) == null) {
                    Map<String, Object> properties = new HashMap<String, Object>();
                    properties.put("name", rs.getString("agencyName"));
                    properties.put("agency", rs.getString("true"));
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
                    properties.put("supplier", rs.getString("true"));
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
                properties.put("value", rs.getDouble("sum"));
                inserter.createRelationship(agencyID, supplierID,
                        DynamicRelationshipType.withName("PAYS"), properties);
                inserter.createRelationship(supplierID, agencyID,
                        DynamicRelationshipType.withName("PAID_BY"), properties);
            }
            // Close the result set, statement and the connection
            rs.close();

            //  lobbying firm/client relationships
            '
            SELECT *, abn as lobbyist_abn
            FROM lobbyists
            INNER JOIN lobbyist_relationships ON lobbyists. "lobbyistID" = lobbyist_relationships. "lobbyistID"
            WHERE "lobbyistClientID" =?;
            '


            ResultSet rs = stmt.executeQuery("SELECT contractnotice.\"agencyName\", "
                    + " (case when \"supplierABN\" != 0 THEN \"supplierABN\"::text ELSE \"supplierName\" END) as supplierID , max(contractnotice.\"supplierName\") as \"supplierName\",sum(value) as sum "
                    + "FROM  public.contractnotice  GROUP BY contractnotice.\"agencyName\", "
                    + " (case when \"supplierABN\" != 0 THEN \"supplierABN\"::text ELSE \"supplierName\" END)");


            // Loop through the result set
            while (rs.next()) {
                long supplierID, agencyID;
                String supplierKey;
                if (agencyIDs.get(rs.getString("agencyName")) == null) {
                    Map<String, Object> properties = new HashMap<String, Object>();
                    properties.put("name", rs.getString("agencyName"));
                    properties.put("agency", rs.getString("true"));
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
                    properties.put("supplier", rs.getString("true"));
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
                properties.put("value", rs.getDouble("sum"));
                inserter.createRelationship(agencyID, supplierID,
                        DynamicRelationshipType.withName("PAYS"), properties);
                inserter.createRelationship(supplierID, agencyID,
                        DynamicRelationshipType.withName("PAID_BY"), properties);
            }
            // Close the result set, statement and the connection
            rs.close();*/

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
