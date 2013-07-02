
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
    Connection conn = null;

    HashMap<String, Long> supplierIDs = new HashMap<String, Long>();
    HashMap<String, Long> agencyIDs = new HashMap<String, Long>();
    HashMap<String, Long> lobbyingFirmIDs = new HashMap<String, Long>();
    HashMap<String, Long> lobbyingClientIDs = new HashMap<String, Long>();
    HashMap<String, Long> donorIDs = new HashMap<String, Long>();
    HashMap<String, Long> partyIDs = new HashMap<String, Long>();
    Label agencyLabel = DynamicLabel.label("Agency");
    Label supplierLabel = DynamicLabel.label("Supplier");
    Label donorLabel = DynamicLabel.label("Political Donor");
    Label partyLabel = DynamicLabel.label("Political Party");
    Label lobbyingClientLabel = DynamicLabel.label("Lobbyist Client");
    Label lobbyistLabel = DynamicLabel.label("Lobbyist");
    Label lobbyingFirmLabel = DynamicLabel.label("Lobbying Firm");
    BatchInserter inserter;

    public static void main(String[] argv) {
        Importer i = new Importer();
        i.importProcess();
    }

    private void dbSetup() {

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
    }

    public void importProcess()

    {
        Map<String, String> config = new HashMap<String, String>();
        config.put("neostore.nodestore.db.mapped_memory", "90M");
        inserter = BatchInserters.inserter("target/batchinserter-example-config", config);
        //BatchInserterIndexProvider indexProvider = new LuceneBatchInserterIndexProvider(inserter);
        //BatchInserterIndex names = indexProvider.nodeIndex("names", MapUtil.stringMap("type", "exact"));
        //names.setCacheCapacity("name", 100000);
        inserter.createDeferredSchemaIndex(agencyLabel).on("name");
        inserter.createDeferredSchemaIndex(donorLabel).on("name");
        inserter.createDeferredSchemaIndex(partyLabel).on("name");
        inserter.createDeferredSchemaIndex(lobbyingClientLabel).on("name");
        inserter.createDeferredSchemaIndex(lobbyistLabel).on("name");
        inserter.createDeferredSchemaIndex(lobbyingFirmLabel).on("name");

        dbSetup();

        pregenerateSuppliers();


        // TODO pregenerate lobbying firms with their lobbyists and mark those firms that are donors
        //pregenerateLobbyingFirms();

        // TODO pregenerate lobbyist clients that are donors
        //pregenerateLobbyingClients();

        agencySupplierRelationships();

        // TODO donor/donation recipient relationships
        donorPartyRelationships();

        // TODO lobbying firm/client relationships
        lobbyingFirmClientRelationships();


//make the changes visible for reading, use this sparsely, requires IO!
//        names.flush();

// Make sure to shut down the index provider
//        indexProvider.shutdown();
        dbClose();
        inserter.shutdown();
    }

    private void dbClose() {
        try {
            // Print all warnings
            getWarnings();

            conn.close();
        } catch (SQLException se) {
            System.out.println("SQL Exception:");

            getExceptions(se);
        }
    }

    private void lobbyingFirmClientRelationships() {

        try {
            // Print all warnings
            getWarnings();

            // Get a statement from the connection
        Statement stmt = conn.createStatement();



            ResultSet rs = stmt.executeQuery("SELECT lobbyists.\"lobbyistID\" as \"lobbyistID\", lobbyists.business_name as lobbyist_business_name, lobbyist_clients.\"lobbyistClientID\" as \"lobbyistClientID\", lobbyist_clients.business_name as client_business_name \n" +
                    "            FROM lobbyists\n" +
                    "            INNER JOIN lobbyist_relationships ON lobbyists. \"lobbyistID\" = lobbyist_relationships. \"lobbyistID\" \n" +
                    "INNER JOIN lobbyist_clients on lobbyist_relationships. \"lobbyistClientID\" = lobbyist_clients. \"lobbyistClientID\"\n" +
                    " where lobbyists.\"lobbyistID\" != 0");


            // Loop through the result set
            while (rs.next()) {
                long lobbyingClientID, lobbyingFirmID;
                String lobbyingClientKey;
                if (lobbyingFirmIDs.get(rs.getString("lobbyistID")) == null) {
                    Map<String, Object> properties = new HashMap<String, Object>();
                    properties.put("name", rs.getString("lobbyist_business_name"));
                    properties.put("lobbyingFirm", "true");
                    lobbyingFirmID = inserter.createNode(properties, lobbyingFirmLabel);
                    lobbyingFirmIDs.put(rs.getString("lobbyistID"), lobbyingFirmID);
                    if (lobbyingFirmID % 100 == 0) {
                        System.out.println("lobbying Firm " + lobbyingFirmID);
                    }
                }
                lobbyingFirmID = lobbyingFirmIDs.get(rs.getString("lobbyistID"));


                // inject some data
                if (lobbyingClientIDs.get(rs.getString("lobbyistClientID")) == null) {
                    Map<String, Object> properties = new HashMap<String, Object>();
                    properties.put("name", rs.getString("client_business_name"));
                    properties.put("lobbyingClient", "true");
                    lobbyingClientID = inserter.createNode(properties, lobbyingClientLabel);
                    lobbyingClientIDs.put(rs.getString("lobbyistClientID"), lobbyingClientID);
                    if (lobbyingClientID % 1000 == 0) {
                        System.out.println("Lobbying Client " + lobbyingClientID);
                    }
                }
                lobbyingClientID = lobbyingClientIDs.get(rs.getString("lobbyistClientID"));


// To set properties on the relationship, use a properties map
// instead of null as the last parameter.

                inserter.createRelationship(lobbyingFirmID, lobbyingClientID,
                        DynamicRelationshipType.withName("HIRES"), null);
                inserter.createRelationship(lobbyingClientID, lobbyingFirmID,
                        DynamicRelationshipType.withName("LOBBIES_FOR"), null);
            }

            // Close the result set, statement and the connection
            rs.close();
            stmt.close();

        } catch (SQLException se) {
            System.out.println("SQL Exception:");

            getExceptions(se);
        }
    }


    private void donorPartyRelationships() {
        try {
            // Print all warnings
            getWarnings();

            // Get a statement from the connection
        Statement stmt = conn.createStatement();

            ResultSet rs = stmt.executeQuery("select \"DonorClientNm\",max(\"RecipientClientNm\") as \"RecipientClientNm\"," +
                    "             sum(\"AmountPaid\") as \"AmountPaid\" from political_donations group by \"DonorClientNm\" order by \"DonorClientNm\" desc");


            // Loop through the result set
            while (rs.next()) {
                long donorID, partyID;

                if (partyIDs.get(rs.getString("RecipientClientNm")) == null) {
                    Map<String, Object> properties = new HashMap<String, Object>();
                    properties.put("name", rs.getString("RecipientClientNm"));
                    properties.put("political_party", "true");
                    partyID = inserter.createNode(properties, partyLabel);
                    partyIDs.put(rs.getString("RecipientClientNm"), partyID);
                    if (partyID % 10 == 0) {
                        System.out.println("Party " + partyID);
                    }
                }
                partyID = partyIDs.get(rs.getString("RecipientClientNm"));


                // inject some data
                if (donorIDs.get(rs.getString("DonorClientNm")) == null) {
                    Map<String, Object> properties = new HashMap<String, Object>();
                    properties.put("name", rs.getString("DonorClientNm"));
                    properties.put("donor", "true");
                    donorID = inserter.createNode(properties, donorLabel);
                    donorIDs.put(rs.getString("DonorClientNm"), donorID);
                    if (donorID % 100 == 0) {
                        System.out.println("Donor " + donorID);
                    }
                }
                donorID = donorIDs.get(rs.getString("DonorClientNm"));


// To set properties on the relationship, use a properties map
// instead of null as the last parameter.
                Map<String, Object> properties = new HashMap<String, Object>();
                properties.put("value", rs.getDouble("AmountPaid"));
                inserter.createRelationship(partyID, donorID,
                        DynamicRelationshipType.withName("PAYS"), properties);
                inserter.createRelationship(donorID, partyID,
                        DynamicRelationshipType.withName("PAID_BY"), properties);
            }
            // Close the result set, statement and the connection
            rs.close();
            stmt.close();

        } catch (SQLException se) {
            System.out.println("SQL Exception:");

            getExceptions(se);
        }
    }

    private void agencySupplierRelationships() {
        //  agency/supplier relationships
        try {
            // Print all warnings
            getWarnings();

            // Get a statement from the connection
            Statement stmt = conn.createStatement();

            // TODO detect suppliers that are also agencies
            ResultSet rs = stmt.executeQuery("SELECT contractnotice.\"agencyName\", "
                    + " (case when \"supplierABN\" != 0 THEN \"supplierABN\"::text ELSE \"supplierName\" END) as supplierID , max(contractnotice.\"supplierName\") as \"supplierName\",sum(value) as sum "
                    + "FROM  public.contractnotice  GROUP BY contractnotice.\"agencyName\", "
                    + " (case when \"supplierABN\" != 0 THEN \"supplierABN\"::text ELSE \"supplierName\" END)");


            // Loop through the result set
            while (rs.next()) {
                long supplierID, agencyID;
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


                supplierID = getOrCreateSupplier(rs.getString("supplierName"), rs.getString("supplierID"));


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
            stmt.close();

        } catch (SQLException se) {
            System.out.println("SQL Exception:");

            getExceptions(se);
        }
    }

    private void pregenerateSuppliers() {
        try {
            // Print all warnings
            getWarnings();

            // Get a statement from the connection
            Statement stmt = conn.createStatement();


            // pregenerate suppliers and mark those that are donors/lobbyist clients

            ResultSet rs = stmt.executeQuery("SELECT min(\"supplierName\") as \"supplierName\",max(\"supplierABN\") as \"supplierABN\",\"lobbyistClientID\" from contractnotice inner join lobbyist_clients on  \"supplierABN\" = \"ABN\"  where \"supplierABN\" is not null group by \"lobbyistClientID\"");
            // TODO include alias lobbyist client names

            while (rs.next()) {
                long supplierID = getOrCreateSupplier(rs.getString("supplierName"), rs.getString("supplierABN"));

                inserter.setNodeLabels(supplierID, supplierLabel, lobbyingClientLabel); // http://api.neo4j.org/2.0.0-M03/org/neo4j/unsafe/batchinsert/BatchInserter.html#setNodeLabels(long, org.neo4j.graphdb.Label...)
                inserter.setNodeProperty(supplierID, "lobbyistclient", "true");
                lobbyingClientIDs.put(rs.getString("lobbyistClientID"), supplierID);

            }
            rs.close();

            // pregenerate suppliers that are also political donors
            rs = stmt.executeQuery("SELECT min(\"supplierName\") as \"supplierName\",max(\"supplierABN\") as \"supplierABN\"," +
                    "\"DonorClientNm\",sum(\"AmountPaid\") from contractnotice inner join political_donations on \"DonorClientNm\" = \"supplierName\" group by \"DonorClientNm\" order by \"DonorClientNm\"");

            while (rs.next()) {
                long supplierID = getOrCreateSupplier(rs.getString("supplierName"), (rs.getString("supplierABN") != null ? rs.getString("supplierABN") : rs.getString("supplierName")));
                if (inserter.nodeHasLabel(supplierID, lobbyingClientLabel)) {
                    inserter.setNodeLabels(supplierID, supplierLabel, donorLabel, lobbyingClientLabel);
                } else {
                    inserter.setNodeLabels(supplierID, supplierLabel, donorLabel);
                }
                inserter.setNodeProperty(supplierID, "donor", "true");
                donorIDs.put(rs.getString("DonorClientNm"), supplierID);

            }
            rs.close();
            stmt.close();

        } catch (SQLException se) {
            System.out.println("SQL Exception:");

            getExceptions(se);
        }
    }

    private void getWarnings() {
        try {
            for (SQLWarning warn = conn.getWarnings(); warn != null; warn = warn.getNextWarning()) {
                System.out.println("SQL Warning:");
                System.out.println("State  : " + warn.getSQLState());
                System.out.println("Message: " + warn.getMessage());
                System.out.println("Error  : " + warn.getErrorCode());
            }
        } catch (SQLException se) {
            System.out.println("SQL Exception:");

            getExceptions(se);
        }
    }

    private void getExceptions(SQLException se) {
        // Loop through the SQL Exceptions
        while (se != null) {
            System.out.println("State  : " + se.getSQLState());
            System.out.println("Message: " + se.getMessage());
            System.out.println("Error  : " + se.getErrorCode());

            se = se.getNextException();
        }
    }

    private long getOrCreateSupplier(String name, String id) {
        if (supplierIDs.get(id) == null) {
            Map<String, Object> properties = new HashMap<String, Object>();
            properties.put("name", name);
            properties.put("supplier", "true");
            long supplierID = inserter.createNode(properties, supplierLabel);
            supplierIDs.put(id, supplierID);
            if (supplierID % 100 == 0) {
                System.out.println("Supplier " + supplierID);
            }
            return supplierID;


        } else {
            return supplierIDs.get(id);
        }
    }
}
