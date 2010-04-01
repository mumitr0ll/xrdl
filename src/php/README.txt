What is in this directory
=========================
This directory contains a utility for generating XRDL service descriptions from
php source code, as well as a utiltity for converting an XRDL service description
into a client based on phpxmlrpc (available at http://phpxmlrpc.sourceforge.net/).

You will need a command line version of php for the utilities to work.

The sections below explain how each utility is used.

Generating XRDL from existing php XML-RPC server code
=====================================================

Generating XRDL from existing code requires going through 4 easy steps as explained below:

- Ensure that all types and methods that you wish to export are documented java-doc style.
  This includes attaching a @type to each and every member of each and every class that 
  you wish to expose across XML-RPC.

- Edit exports.php and change the $exports variable to include the classes and methods 
  that you wish to export. Only classes and methods should be included. Required types will
  be automatically exported.
  Please remember to include classes containing types and methods that you wish to export

- Execute php ./xrdl-generator.php --url <url> --ns <ns> --name <name> (<url> must point to where
  clients can access your service, whereas <ns> and <name> can be set to basically whatever you 
  want, as they are just hints for the client).

- Examine the output of what you just executed. If everything looks good, store the output in an 
  appropriately named file. This is the XRDL service definition. You may now use this to automatically 
  generate clients.

Generating a php XML-RPC client from an existing XRDL definition
================================================================

Generating a php client given an XRDL definition requires the following steps 
to be completed:

- Obtain an XRDL definition for the service you wish to access. Store it in 
  a file somewhere in the local file system.

- Execute php ./xml-rpc-client-generator.php --file <path to XRDL-file>

- Examine the output of what you just executed (maybe pass it through php -l?). 
If everything looks good, store the output in a .php-file somewhere. You now 
have a client class, with methods matching those exported by the XML-RPC service.

