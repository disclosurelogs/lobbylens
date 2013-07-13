import org.apache.commons.lang3.StringUtils;
import org.neo4j.graphdb.DynamicLabel;
import org.neo4j.graphdb.DynamicRelationshipType;
import org.neo4j.graphdb.Label;
import org.neo4j.unsafe.batchinsert.BatchInserter;
import org.neo4j.unsafe.batchinsert.BatchInserters;

import java.sql.*;
import java.util.HashMap;
import java.util.Map;

public class Importer {
    Connection conn = null;

    HashMap<String, Long> supplierIDs = new HashMap<String, Long>();
    HashMap<String, Long> agencyIDs = new HashMap<String, Long>();
    HashMap<String, Long> lobbyingFirmIDs = new HashMap<String, Long>();
    HashMap<String, Long> lobbyingClientIDs = new HashMap<String, Long>();
    HashMap<String, Long> donationRecipientIDs = new HashMap<String, Long>();
    HashMap<String, Long> donorIDs = new HashMap<String, Long>();
    HashMap<String, Long> partyIDs = new HashMap<String, Long>();
    Label agencyLabel = DynamicLabel.label("Agency");
    Label supplierLabel = DynamicLabel.label("Supplier");
    Label donorLabel = DynamicLabel.label("Political Donor");
    Label donationRecipientLabel = DynamicLabel.label("Political Party");
    Label partyLabel = DynamicLabel.label("Political Party");
    Label lobbyingClientLabel = DynamicLabel.label("Lobbyist Client");
    Label lobbyistLabel = DynamicLabel.label("Lobbyist");
    Label lobbyingFirmLabel = DynamicLabel.label("Lobbying Firm");
    BatchInserter inserter;

    String cleanseRegex = StringUtils.replace(StringUtils.replace(StringUtils.replace(StringUtils.join(new String[]{
            "Ltd",
            "Limited",
            "Australiasia",
            "The ",
            "(NSW)",
            "(QLD)",
            "Pty",
            "Ltd",
            "Aust.",
            "(NSW/ACT)",
            "Aust ",
            "(Aus)",
            "(Inc)",
            "(WA)",
            "(Southern Region)",
            "Contractors",
            "P/L",
            "(N.S.W.)",
            "(SA Branch)",
            "NSW",
            "Inc.",
            "Inc",
            "Incorporated",
            "SA Branch",
            "ACT",
            "QLD",
            ", SA",
            " WA",
            "- QLD Services Branch",
            "- Central and Southern Q",
            "- SA and NT Branch",
            "- TAS",
            "NSW & ACT Services Branch",
            "SA-NT Branch",
            "- National Office",
            "- Victoria Branch",
            "- National",
            "- Victoria Branch",
            "(Greater SA)",
            "(SA)",
            "(VIC)",
            "Hornibrook",
            "- NATIONAL",
            ". .",
            "(IAG)",
            "(NSW Div)",
            "(Queensland Branch)",
            "(ACT/NSW Bra",
            "(SA/NT)",
            ", WA Branch",
            "- a coalition of professional associations and firms"}, "|"), "(", "\\("), ")", "\\)"), ".", "\\.");

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
        inserter.createDeferredSchemaIndex(agencyLabel).on("name");
        inserter.createDeferredSchemaIndex(donorLabel).on("name");
        inserter.createDeferredSchemaIndex(donationRecipientLabel).on("name");
        inserter.createDeferredSchemaIndex(partyLabel).on("name");
        inserter.createDeferredSchemaIndex(lobbyingClientLabel).on("name");
        inserter.createDeferredSchemaIndex(lobbyistLabel).on("name");
        inserter.createDeferredSchemaIndex(lobbyingFirmLabel).on("name");

        dbSetup();
        System.out.println(cleanseRegex);
        pregenerateSuppliers();

        // TODO  regexp_replace('Thomas', '.[mN]a.', 'M') http://www.postgresql.org/docs/9.1/static/functions-matching.html#FUNCTIONS-POSIX-REGEXP

// TODO http://stackoverflow.com/questions/3772584/postgresql-join-using-like-ilike

        // pregenerate lobbying firms and mark those firms that are donors
        // TODO include individual lobbyists and their previous gov represetitive data
        pregenerateLobbyingFirms();

        // pregenerate lobbyist clients that are donors
        pregenerateLobbyingClients();

        agencySupplierRelationships();

        // donor/donation recipient/party relationships
        donorRecipientPartyRelationships();

        // lobbying firm/client relationships
        lobbyingFirmClientRelationships();

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
                        DynamicRelationshipType.withName("HIRES_TO_LOBBY"), null);
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


    private void donorRecipientPartyRelationships() {
        try {
            // Print all warnings
            getWarnings();

            // Get a statement from the connection
            Statement stmt = conn.createStatement();
            // select sum("AmountPaid"), party from political_donations inner join donation_recipient_to_party on political_donations."RecipientClientNm" = donation_recipient_to_party."RecipientClientNm" group by party

            //select sum("AmountPaid"), political_donations."RecipientClientNm" from political_donations inner join donation_recipient_to_party on political_donations."RecipientClientNm" = donation_recipient_to_party."RecipientClientNm" where party is null group by political_donations."RecipientClientNm";

            ResultSet rs = stmt.executeQuery("select \"DonorClientNm\",political_donations.\"RecipientClientNm\", max(party) as party," +
                    "             sum(\"AmountPaid\") as \"AmountPaid\" from political_donations" +
                    " inner join donation_recipient_to_party on political_donations.\"RecipientClientNm\" = donation_recipient_to_party.\"RecipientClientNm\"" +
                    " group by \"DonorClientNm\", political_donations.\"RecipientClientNm\" order by \"DonorClientNm\" desc");


            // Loop through the result set
            while (rs.next()) {
                long donorID, donationRecipientID, partyID;
                partyID = -1;
                if (rs.getString("party") != null)     {
                    if (partyIDs.get(rs.getString("party")) == null) {
                        Map<String, Object> properties = new HashMap<String, Object>();
                        properties.put("name", rs.getString("party"));
                        properties.put("political_party", "true");
                        partyID = inserter.createNode(properties, partyLabel);
                        partyIDs.put(rs.getString("party"), partyID);
                        if (partyID % 10 == 0) {
                            System.out.println("Party " + partyID);
                        }
                        partyID = partyIDs.get(rs.getString("party"));
                    }
            }


                if (donationRecipientIDs.get(rs.getString("RecipientClientNm")) == null) {
                    Map<String, Object> properties = new HashMap<String, Object>();
                    properties.put("name", rs.getString("RecipientClientNm"));
                    properties.put("political_donation_recipient", "true");
                    donationRecipientID = inserter.createNode(properties, donationRecipientLabel);
                    donationRecipientIDs.put(rs.getString("RecipientClientNm"), donationRecipientID);
                    if (donationRecipientID % 10 == 0) {
                        System.out.println("donationRecipient " + donationRecipientID);
                    }
                }
                donationRecipientID = donationRecipientIDs.get(rs.getString("RecipientClientNm"));

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
                if (partyID != -1) {
                    inserter.createRelationship(partyID, donorID,
                            DynamicRelationshipType.withName("DONATES_TO"), properties);
                    inserter.createRelationship(donorID, partyID,
                            DynamicRelationshipType.withName("RECEIVES_DONATIONS_FROM"), properties);
                    inserter.createRelationship(donationRecipientID, partyID,
                            DynamicRelationshipType.withName("ASSOCIATED_WITH"), properties);
                    inserter.createRelationship(partyID, donationRecipientID,
                            DynamicRelationshipType.withName("ASSOCIATED_WITH"), properties);
                }
                inserter.createRelationship(donationRecipientID, donorID,
                        DynamicRelationshipType.withName("DONATES_TO"), properties);
                inserter.createRelationship(donorID, donationRecipientID,
                        DynamicRelationshipType.withName("RECEIVES_DONATIONS_FROM"), properties);

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

    private void pregenerateLobbyingFirms() {
        try {
            // Print all warnings
            getWarnings();

            // Get a statement from the connection
            Statement stmt = conn.createStatement();


            // pregenerate donors+lobbying firms

            ResultSet rs = stmt.executeQuery("SELECT max(\"lobbyistID\") as \"lobbyistID\", min(\"business_name\"),\"DonorClientNm\",sum(\"AmountPaid\") from lobbyists inner join political_donations on \"DonorClientNm\" = \"business_name\" or \"DonorClientNm\" = \"trading_name\" group by \"DonorClientNm\" order by \"DonorClientNm\"; ");

            while (rs.next()) {
                if (donorIDs.get(rs.getString("DonorClientNm")) == null) {
                    Map<String, Object> properties = new HashMap<String, Object>();
                    properties.put("name", rs.getString("DonorClientNm"));
                    properties.put("donor", "true");
                    properties.put("lobbying_firm", "true");
                    long donorID = inserter.createNode(properties, donorLabel, lobbyingFirmLabel);
                    donorIDs.put(rs.getString("DonorClientNm"), donorID);
                    lobbyingFirmIDs.put(rs.getString("lobbyistID"), donorID);
                    if (donorID % 100 == 0) {
                        System.out.println("donor + lobbyist " + donorID);
                    }
                }
            }

            rs.close();
            stmt.close();

        } catch (SQLException se) {
            System.out.println("SQL Exception:");

            getExceptions(se);
        }
    }

    private void pregenerateLobbyingClients() {
        try {
            // Print all warnings
            getWarnings();

            // Get a statement from the connection
            Statement stmt = conn.createStatement();


            // pregenerate donors+lobbyist clients

            ResultSet rs = stmt.executeQuery("SELECT max(\"lobbyistClientID\") as \"lobbyistClientID\", min(\"business_name\"),\"DonorClientNm\",sum(\"AmountPaid\") from lobbyist_clients inner join political_donations on \"DonorClientNm\" = \"business_name\" group by \"DonorClientNm\" order by \"DonorClientNm\";");

            while (rs.next()) {
                if (donorIDs.get(rs.getString("DonorClientNm")) == null) {
                    Map<String, Object> properties = new HashMap<String, Object>();
                    properties.put("name", rs.getString("DonorClientNm"));
                    properties.put("donor", "true");
                    properties.put("lobbying_client", "true");
                    long donorID = inserter.createNode(properties, donorLabel, lobbyingClientLabel);
                    donorIDs.put(rs.getString("DonorClientNm"), donorID);
                    lobbyingClientIDs.put(rs.getString("lobbyistClientID"), donorID);
                    if (donorID % 100 == 0) {
                        System.out.println("donor + lobbying client " + donorID);
                    }
                }
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
                StackTraceElement[] stacktrace = Thread.currentThread().getStackTrace();
                StackTraceElement e = stacktrace[2];//maybe this number needs to be corrected
                System.out.println("Calling method  : " + e.getMethodName());
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
            StackTraceElement[] stacktrace = Thread.currentThread().getStackTrace();
            StackTraceElement e = stacktrace[2];//maybe this number needs to be corrected
            System.out.println("Calling method  : " + e.getMethodName());
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
