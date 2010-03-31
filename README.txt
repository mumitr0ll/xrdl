What is XRDL
============
XRDL aims to be a complete solution to the inherently error prone 
process of writing XML-RPC clients in various languages. I currently maintain 
XML-RPC client classes in C++, php, actionscript 3, C# and javascript, so I 
have a fairly strong interest in automating client generation.

XRDL is intended to be simple, as most people seem to choose XML-RPC for its
simplicity.

XRLD is currently in its infancy, but as XML-RPC is basically the data 
transfer protocol that we use throughout our architecture, I expect that 
additional work will go into XRDL, to save time in the long run.

How do I use XRDL
=================
XRDL currently supports:
- Generating service definitions for XML-RPC services written in php
- Generating clients for use with the phpxmlrpc-clients available at 
  http://phpxmlrpc.sourceforge.net/
- Generating clients for use with libmaia (a Qt-based XML-RPC library 
  for C++)
- Validating service definitions using XSD.

If you have an XML-RPC service written in php, for which you wish to 
automatically generate clients, please refer to src/php/README.txt

If you wish to generate an XML-RPC client based on an XRDL definition, 
please refer to either src/php/README.txt or src/c++/README.txt depending 
on the target language.

What remains to be done
=======================
XRDL is mostly in need of a thorough review. Additionally, generators for 
more platforms and languages need to be written to make XRDL truly useful.

How do I contribute
===================
Please get in touch with me at soren@overgaard.org if you wish to contribute
code.
