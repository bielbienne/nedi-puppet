NeDi 1.0.9
==========

Introduction
------------
NeDi discovers, maps and inventories your network devices and tracks connected end-nodes.
It contains a lot of features in a user-friendly GUI for managing enterprise networks.
For example: MAC address mapping/tracking, traffic & error graphing, uptime monitoring,
correlate collected syslog & trap messages with customizable notification, drawing
network maps, extensive reporting features such as device software, PoE usage, disabled
interfaces, link errors, switch usage and many more. It's modular architecture allows for
simple integration with other tools. For example Cacti graphs can be created purely based
on discovered information. Due to NeDi's versatility things like printer resources can be
monitored as well...

Changes from 1.0.8
------------------
You'll need to install p5-socket6 (or comment line 18 and 19 in libdb.pm, if you don't use Postgres)
Postgres support is still experimental!

Features:
- Writing cmd_user file as php to hide from unauthorized access. The output log is still accessable with authentication and are included as iframes in Devices-Write for better readability.
- Selectable display of icons, panels, NeDimaps, Openstreetmaps (cached) and weather information (via openweathermap API) in Topology-Table. Googlemaps can still be enabled in User-Profile instead of using openstreetmaps.
- NeDimaps are displayed in Devices-Status and Nodes-Status to visualize topology information.
- The html/foto directory has been replaced by topo allowing to cache OSM maps and hold background maps or actual fotos and docs in a hierarchical manner.
- When showing nodes in maps, FD/HD display is properly shown on links now.
- Device shapes are more accurately modeled after device icons. 
- Removed the jit-code for Topology-Maps in favour of the powerful d3js library.
- Adding generic panels rather than colored rack elements in Topology-Table.
- Monitoring status and full-size-panel maps can be drawn as well now (various other details improved).
- Pulldown menu is replaced by buttons on Mobile devices. 
- Per target thresholds and notify settings in Monitoring-Setup. Settings in nedi.conf are still used for unmonitored devices.
- Added Chart.js for nice html5 charts in Reports and interface radar chart, for easier troubleshooting.
- Using cdpCacheNativeVLAN rather than cdpCacheVlanID (which actually is VoIP VID).
- Available access ports (meaning they've been down for > "retire" days) in Topo-Tables, Device-Status and Device-List
- Interface last change considers inbound traffic to detect changes, even it's only discovered with link down.
- Generating links based on Bridge-Forwarding tables, MAC and IP interfaces.
- Config last change and write are used on supported devices to detect unsaved configs and device reboots. This will optimize backups in addition.
- Devices like clouds or rackservers can be manually created now.
- Using system commands for improved performance in System-Export.
- Added send and receive strings to monitoring (e.g. to match ntp, dns and http responses)
- Added m(ode) option to query.php for json output.
- Rewrote name,contact,location and SN mapping (can also be used to handle active/standby firewalls with the same name, like ASAs)
- Added name2loc option for networks using a pattern in the device name to specify its location
- Mapping HP MSM APs with name learned from controller as they always send their SN via CDP
- Showing vendor icon in Devices-Status and Devices-List with Google link for easier handling
- Replacing condition A and B with up to 4 filters in most modules as well as the aged calendar.
- Filter templates provide quick results in modules.
- Added bulk delete and filter templates in Devices-List
- Optimizing backend for Postgres support.
- Added per interface thresholds for traffic, broadcasts and mac flood alerts (can be changed in Devices-Interfaces)
- Added Postgres support (which required additional DB changes)
- Added config file option to syslog.pl and stati.pl
- Made SMS sending options configurable in nedi.conf
- Added -c option to specify a preferred community
- Catching more SNMP errors in modules and 
- Added get-next option for CPU and temperature OIDs by adding an 'N' in .def for inconcistent models with samesysobjid
- Changed skipif to skippol in nedi.conf to be clearer (you can skip forwarding tables globally, not just IF info)
- Extended skipping IF info and forward-tables to wireless controllers for more granular control
- Added notify c/C options in nedi.conf for more granular notifications on CLI errors
- Default page changed to Other-Invoice during December in hopes for Xmas@NeDi (it'll revert to User-Profile for the rest of the year)
- Many smaller bug fixes and optimizations

DB Changes from 1.0.8
---------------------
Copy & paste the commands below into System-Export...

ALTER TABLE devices ADD COLUMN cfgchange INT unsigned default 0;
ALTER TABLE devices ADD COLUMN cfgstatus CHAR(2) DEFAULT '--';
ALTER TABLE modules MODIFY COLUMN modidx SMALLINT UNSIGNED DEFAULT 0;

-- From nedi-240 (Not needed, when upgrading directly from 1.0.8)
ALTER TABLE configs DROP COLUMN dvcfgchg;

-- export stock into XLS as whole stock mgmt has been redesigned
DROP TABLE stock;
CREATE TABLE stock (
state TINYINT UNSIGNED DEFAULT 0,
serial VARCHAR(32) NOT NULL UNIQUE,
type VARCHAR(32) DEFAULT 0,
asset VARCHAR(32) DEFAULT '',
location VARCHAR(255) DEFAULT '',
source VARCHAR(32) default '-',
cost INT UNSIGNED DEFAULT 0,
ponumber VARCHAR(32) DEFAULT '',
time INT UNSIGNED DEFAULT 0,
partner VARCHAR(32) DEFAULT '',
startmaint INT UNSIGNED DEFAULT 0,
endmaint INT UNSIGNED DEFAULT 0,
lastwty INT UNSIGNED DEFAULT 0,
comment VARCHAR(255) DEFAULT '',
usrname VARCHAR(32) DEFAULT '',
asupdate INT UNSIGNED DEFAULT 0,
INDEX(serial) );

-- This was needed for Postgres integration, but needs to be applied for mysql as well!
ALTER TABLE interfaces change COLUMN trafwarn brcalert SMALLINT UNSIGNED DEFAULT 0;
ALTER TABLE users change COLUMN user usrname VARCHAR(32) NOT NULL UNIQUE;
ALTER TABLE stolen change COLUMN user usrname VARCHAR(32) DEFAULT '';
ALTER TABLE chat change COLUMN user usrname VARCHAR(32) DEFAULT '';
ALTER TABLE nodetrack change COLUMN user usrname VARCHAR(32) DEFAULT '';
ALTER TABLE incidents change COLUMN user usrname VARCHAR(32) DEFAULT '';
ALTER TABLE incidents change COLUMN start startinc INT UNSIGNED DEFAULT 0;
ALTER TABLE incidents change COLUMN end endinc INT UNSIGNED DEFAULT 0;

-- From nedi-321 (no changes :-)
