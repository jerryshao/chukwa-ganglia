package org.apache.hadoop.chukwa.contrib.chukwa_ganglia;

import java.util.HashMap;
import java.util.Map;
import java.util.TimerTask;

import org.apache.hadoop.chukwa.ChunkImpl;
import org.apache.hadoop.chukwa.datacollection.ChunkReceiver;
import org.apache.hadoop.chukwa.util.ExceptionUtil;
import org.apache.log4j.Logger;
import org.hyperic.sigar.CpuInfo;
import org.hyperic.sigar.CpuPerc;
import org.hyperic.sigar.FileSystem;
import org.hyperic.sigar.FileSystemUsage;
import org.hyperic.sigar.Mem;
import org.hyperic.sigar.NetInterfaceStat;
import org.hyperic.sigar.ProcStat;
import org.hyperic.sigar.Sigar;
import org.hyperic.sigar.SigarException;
import org.hyperic.sigar.Swap;
import org.json.simple.JSONObject;

final class SystemMetricsRunner extends TimerTask {
	private static Logger log
		= Logger.getLogger(SystemMetricsRunner.class);
	public final static String SOURCE = "GangliaSystemMetrics";
	public final static long MULTIPLIER = 1000; 
		
	private Map<String, SystemMetricsProtocol> sysInfoMap = 
			new HashMap<String, SystemMetricsProtocol>();
	{
		sysInfoMap.put("CPU", new CPUInfo());
		sysInfoMap.put("system", new SystemInfo());
		sysInfoMap.put("memory", new MemoryInfo());
		sysInfoMap.put("network", new NetworkInfo());
		sysInfoMap.put("disk", new DiskInfo());	
	}
	
	private ChunkReceiver receiver = null;
	private int timerPeriod = 15;
	private Sigar sigar = null;
	private long sendOffset = 0;
	private GangliaSystemMetricsAdaptor sysMetricsAdaptor = null;
	private Map<String, Map<String, Long>> previousNetworkStats = 
			new HashMap<String, Map<String, Long>>();
	private Map<String, Map<String, Double>> previousDiskStats = 
			new HashMap<String, Map<String, Double>>();
	
	private static long currTime = 0l;
	
	SystemMetricsRunner(ChunkReceiver r, int period, 
			GangliaSystemMetricsAdaptor s) {
		receiver = r;
		timerPeriod = period / 1000;
		sysMetricsAdaptor = s;
		sigar = new Sigar();
	}

	@Override
	public void run() {
		JSONObject sysMetricObj = new JSONObject();
		if (currTime == 0) {
			currTime = System.currentTimeMillis() / 1000;
			currTime = currTime - currTime % timerPeriod;
		} else {
			currTime += timerPeriod;
		}
		
		for (Map.Entry<String, SystemMetricsProtocol> e : 
			sysInfoMap.entrySet()) {
			try {
				e.getValue().getSystemInfo(sysMetricObj, currTime);
			} catch (Exception t) {
				log.error(ExceptionUtil.getStackTrace(t));
			}
		}
	    
	    byte[] data = sysMetricObj.toString().getBytes();
	    sendOffset += data.length;
	    ChunkImpl c = new ChunkImpl("GangliaSystemMetrics", "Sigar", 
	    		sendOffset, data, sysMetricsAdaptor);
	    try {
			receiver.add(c);
		} catch (InterruptedException e) {
			log.error(ExceptionUtil.getStackTrace(e));
		}
	}
	
	@SuppressWarnings("unchecked")
	private JSONObject metricCombinerHelper(Object val, String type, 
			String unit, String cf, String af, long ts) {
		JSONObject obj = new JSONObject();
		obj.put("VAL", val);
		obj.put("TYPE", type);
		obj.put("UNITS", unit);
		obj.put("CF", cf);
		obj.put("AF", af);
		obj.put("SOURCE", SOURCE);
		obj.put("LASTUPDATE", ts);
		obj.put("PERIOD", timerPeriod);
		
		return obj;		
	}
	
	private class CPUInfo implements SystemMetricsProtocol {
		@SuppressWarnings("unchecked")
		public  void getSystemInfo(JSONObject storeObj, long ts)
				throws SigarException {
			JSONObject cpuInfo = new JSONObject();
			
			Map<String, JSONObject> cpuMap = 
					new HashMap<String, JSONObject>();
		
			CpuInfo[] cpuinfo = sigar.getCpuInfoList();
			//get cpu_speed
			long cpuMhz = cpuinfo[0].getMhz() * MULTIPLIER;
			cpuMap.put("cpu_speed", 
				metricCombinerHelper(cpuMhz,"long", "Mhz", "LAST", "NONE", ts));
			
			//get cpu_num
			long cpuCores = cpuinfo[0].getTotalCores() * MULTIPLIER;
			cpuMap.put("cpu_num", 
				metricCombinerHelper(cpuCores, "long", "CPUs", "LAST", "SUM", ts));;		
			
			//get cpu utilization
			Map<String, Double> cpuSumMap = new HashMap<String, Double>();
			cpuSumMap.put("cpu_user", 0.0);
			cpuSumMap.put("cpu_system", 0.0);
			cpuSumMap.put("cpu_wio", 0.0);
			cpuSumMap.put("cpu_nice", 0.0);
			cpuSumMap.put("cpu_idle", 0.0);
			
			CpuPerc[] cpuPerc = sigar.getCpuPercList();
			for (int i = 0; i < cpuPerc.length; i++) {
				cpuSumMap.put("cpu_user", cpuSumMap.get("cpu_user") +
						cpuPerc[i].getUser() / cpuPerc.length * 100);
				cpuSumMap.put("cpu_system", cpuSumMap.get("cpu_system") +
						cpuPerc[i].getSys() / cpuPerc.length * 100);
				cpuSumMap.put("cpu_wio", cpuSumMap.get("cpu_wio") +
						cpuPerc[i].getWait() / cpuPerc.length * 100);
				cpuSumMap.put("cpu_nice", cpuSumMap.get("cpu_nice") +
						cpuPerc[i].getNice() / cpuPerc.length * 100);
				cpuSumMap.put("cpu_idle", cpuSumMap.get("cpu_idle") +
						cpuPerc[i].getIdle() / cpuPerc.length * 100);
			}
			
			for (Map.Entry<String, Double> e : cpuSumMap.entrySet()) {
				cpuMap.put(e.getKey(), metricCombinerHelper(
						(long)(e.getValue() * MULTIPLIER), "double", "%", "AVG", "AVG", ts));
			}
			
			cpuInfo.putAll(cpuMap);
			storeObj.put("cpu", cpuInfo);
		}
	}
	
	private class SystemInfo implements SystemMetricsProtocol {
		@SuppressWarnings("unchecked")
		public void getSystemInfo(JSONObject storeObj, long ts)
				throws SigarException {
			JSONObject sysInfo = new JSONObject();
			Map<String, JSONObject> sysMap = 
					new HashMap<String, JSONObject>();
		
			//get boottime
			long bootTime = (long)(sigar.getUptime().getUptime() * MULTIPLIER);
			sysMap.put("boottime",
				metricCombinerHelper(bootTime, "double", "s", "LAST", "NONE", ts));
			
		    ProcStat proc = sigar.getProcStat();
			//get proc total
		    long procTotal = proc.getTotal() * MULTIPLIER;
		    sysMap.put("proc_total",
		    	metricCombinerHelper(procTotal, "long", " ", "AVG", "SUM", ts));
		    
			//get proc running
		    long procRun = proc.getRunning() * MULTIPLIER; 
		    sysMap.put("proc_run",
		    	metricCombinerHelper(procRun, "long", " ", "AVG", "SUM", ts));
		    
			//get system load average
			String[] names = {"load_one", "load_five", "load_fifteen"};
			double[] loadavg = sigar.getLoadAverage();
			for (int i = 0; i < loadavg.length; i++) {
				sysMap.put(names[i], metricCombinerHelper(
						(long)(loadavg[i] * MULTIPLIER), "double", " ", "AVG", "AVG", ts));
			}
			
			sysInfo.putAll(sysMap);
			storeObj.put("system", sysInfo);	
		}
	}
	
	private class MemoryInfo implements SystemMetricsProtocol {
		@SuppressWarnings("unchecked")
		public void getSystemInfo(JSONObject storeObj, long ts)
			throws SigarException {
			JSONObject memInfo = new JSONObject();
			Map<String, JSONObject> memMap = 
					new HashMap<String, JSONObject>();
			
			Mem mem = sigar.getMem();
			//get memory total
			long memTotal = mem.getTotal() / 1024 / 1024 * MULTIPLIER;
		    memMap.put("mem_total",
		    	metricCombinerHelper(memTotal, "long", "MB", "AVG", "SUM", ts));

		    //get memory used
		    long memUsed = mem.getUsed() / 1024 / 1024 * MULTIPLIER;
		    memMap.put("mem_used",
		    	metricCombinerHelper(memUsed, "long", "MB", "AVG", "SUM", ts));
		    
		    //get memory free
		    long memFree = mem.getFree() / 1024 / 1024 * MULTIPLIER;
		    memMap.put("mem_free",
		    	metricCombinerHelper(memFree, "long", "MB", "AVG", "SUM", ts));
		    
		    memInfo.putAll(memMap);
		    storeObj.put("mem", memInfo);
		    
		    
		    JSONObject swapInfo = new JSONObject();
		    Map<String, JSONObject> swapMap = 
		    		new HashMap<String, JSONObject>();
		    
		    Swap swap = sigar.getSwap();
		    //get swap total
		    long swapTotal = swap.getTotal() / 1024 / 1024 * MULTIPLIER;
		    swapMap.put("swap_total",
		    	metricCombinerHelper(swapTotal, "long", "MB", "AVG", "SUM", ts));
		    
		    //get Swap used
		    long swapUsed = swap.getUsed() / 1024 / 1024 * MULTIPLIER;
		    swapMap.put("swap_used",
		    	metricCombinerHelper(swapUsed, "long", "MB", "AVG", "SUM", ts));
		    
		    swapInfo.putAll(swapMap);
		    storeObj.put("swap", swapInfo);
		}
	}
	
	private class NetworkInfo implements SystemMetricsProtocol {
		@SuppressWarnings("unchecked")
		public void getSystemInfo(JSONObject storeObj, long ts)
			throws SigarException {
			JSONObject networkInfo = new JSONObject();
			Map<String, JSONObject> networkMap = 
					new HashMap<String, JSONObject>();
			
			String[] netIf = sigar.getNetInterfaceList();
		    Map<String, Double> netSumMap = new HashMap<String, Double>();
		    netSumMap.put("bytes_in", 0.0);
		    netSumMap.put("bytes_in_dropped", 0.0);
		    netSumMap.put("bytes_in_errors", 0.0);
		    netSumMap.put("pkts_in", 0.0);
		          
		    netSumMap.put("bytes_out", 0.0);
		    netSumMap.put("bytes_out_dropped", 0.0);
		    netSumMap.put("bytes_out_errors", 0.0);
		    netSumMap.put("pkts_out", 0.0);
		    
		    for (int i = 0; i < netIf.length; i++) {
		    	if (netIf[i].equalsIgnoreCase("lo")) {
		    		//ignore loopback net interface
		    		continue;
		    	}
		    	
		    	NetInterfaceStat net = sigar.getNetInterfaceStat(netIf[i]);
		    	long rxBytes = net.getRxBytes();
		    	long rxDropped = net.getRxDropped();
		    	long rxErrors = net.getRxErrors();
		    	long rxPackets = net.getRxPackets();
			  
		    	long txBytes = net.getTxBytes();
		    	long txDropped = net.getTxDropped();
		    	long txErrors = net.getTxErrors();
		    	long txPackets = net.getTxPackets();
		      
		      Map<String, Long> netStatusMap = previousNetworkStats.get(netIf[i]);
		      if (netStatusMap != null) {	
		    	  double deltaRxBytes = (rxBytes - netStatusMap.get("bytes_in"))
		    			  / (timerPeriod * 1.0);
		    	  networkMap.put(netIf[i] + "_bytes_in", 
		    			  metricCombinerHelper((long)(deltaRxBytes * MULTIPLIER), "double", 
				    				"bytes/sec", "AVG", "AVG", ts));
		    	  
		     	  double deltaRxDropped = 
		    			(rxDropped - netStatusMap.get("bytes_in_dropped")) 
		    			/ (timerPeriod * 1.0);
		    	  networkMap.put(netIf[i] + "_bytes_in_dropped", 
		    			  metricCombinerHelper((long)(deltaRxDropped * MULTIPLIER), "double", 
				    				"bytes/sec", "AVG", "AVG", ts));
		    	  
		    	  double deltaRxErrors = 
		    			(rxErrors - netStatusMap.get("bytes_in_errors"))
		    			/ (timerPeriod * 1.0);
		    	  networkMap.put(netIf[i] + "_bytes_in_errors", 
		    			  metricCombinerHelper((long)(deltaRxErrors * MULTIPLIER), "double", 
				    				"bytes/sec", "AVG", "AVG", ts));
		    	  
		    	  double deltaRxPackets = 
		    			(rxPackets - netStatusMap.get("pkts_in")) 
		    			/ (timerPeriod * 1.0);
		    	  networkMap.put(netIf[i] + "_pkts_in", 
		    			  metricCombinerHelper((long)(deltaRxPackets * MULTIPLIER), "double", 
				    				"packets/sec", "AVG", "AVG", ts));
		    	 
		    	  double deltaTxBytes = (txBytes - netStatusMap.get("bytes_out"))
		    			  / (timerPeriod * 1.0);
		    	  networkMap.put(netIf[i] + "_bytes_out", 
		    			  metricCombinerHelper((long)(deltaTxBytes * MULTIPLIER), "double", 
				    				"bytes/sec", "AVG", "AVG", ts));
		    	  
		    	  double deltaTxDropped = 
		    			(txDropped - netStatusMap.get("bytes_out_dropped"))
		    			/ (timerPeriod * 1.0);
		    	  networkMap.put(netIf[i] + "_bytes_out_dropped", 
		    			  metricCombinerHelper((long)(deltaTxDropped * MULTIPLIER), "double", 
				    				"bytes/sec", "AVG", "AVG", ts));
		    	  
		    	  double deltaTxErrors = 
		    			(txErrors - netStatusMap.get("bytes_out_errors")) 
		    			/ (timerPeriod * 1.0);
		    	  networkMap.put(netIf[i] + "_bytes_out_errors", 
		    			  metricCombinerHelper((long)(deltaTxErrors * MULTIPLIER), "double", 
				    				"bytes/sec", "AVG", "AVG", ts));
		    	  
		    	  double deltaTxPackets = 
		    			(txPackets - netStatusMap.get("pkts_out")) 
		    			/ (timerPeriod * 1.0);
		    	  networkMap.put(netIf[i] + "_pkts_out", 
		    			  metricCombinerHelper((long)(deltaTxPackets * MULTIPLIER), "double", 
				    				"packets/sec", "AVG", "AVG", ts));
		    		    	
		    	  //sum the result
		    	  netSumMap.put("bytes_in", 
		        		netSumMap.get("bytes_in") + deltaRxBytes);
		    	  netSumMap.put("bytes_in_dropped", 
		        		netSumMap.get("bytes_in_dropped") + deltaRxDropped);
		    	  netSumMap.put("bytes_in_errors",
		        		netSumMap.get("bytes_in_errors") + deltaRxErrors);
		    	  netSumMap.put("pkts_in",
		        		netSumMap.get("pkts_in") + deltaRxPackets);
		        
		    	  netSumMap.put("bytes_out", 
		        		netSumMap.get("bytes_out") + deltaTxBytes);
		    	  netSumMap.put("bytes_out_dropped", 
		        		netSumMap.get("bytes_out_dropped") + deltaTxDropped);
		    	  netSumMap.put("bytes_out_errors",
		        		netSumMap.get("bytes_out_errors") + deltaTxErrors);
		    	  netSumMap.put("pkts_out",
		        		netSumMap.get("pkts_out") + deltaTxPackets);
		      	} else {
		      		netStatusMap = new HashMap<String, Long>();
		      		previousNetworkStats.put(netIf[i], netStatusMap);
		      	}
		      
		      	netStatusMap.put("bytes_in", rxBytes);
		      	netStatusMap.put("bytes_in_dropped", rxDropped);
		      	netStatusMap.put("bytes_in_errors", rxErrors);
		      	netStatusMap.put("pkts_in", rxPackets);
		      
		      	netStatusMap.put("bytes_out", txBytes);
		      	netStatusMap.put("bytes_out_dropped", txDropped);
		      	netStatusMap.put("bytes_out_errors", txErrors);
		      	netStatusMap.put("pkts_out", txPackets);
		    }
		    
		    for (Map.Entry<String, Double> e : netSumMap.entrySet()) {
			    if (e.getKey().startsWith("bytes")) {
			    	networkMap.put(e.getKey(),
			    		metricCombinerHelper((long)(e.getValue() * MULTIPLIER), "double", 
			    				"bytes/sec", "AVG", "AVG", ts));
			    } else {
			    	networkMap.put(e.getKey(),
			    		metricCombinerHelper((long)(e.getValue() * MULTIPLIER), "double", 
			    				"packets/sec", "AVG", "AVG", ts));
			    }
		    }
		    
		    networkInfo.putAll(networkMap);
		    storeObj.put("network", networkInfo);
		}
	}
	
	private class DiskInfo implements SystemMetricsProtocol {
		@SuppressWarnings("unchecked")
		public void getSystemInfo(JSONObject storeObj, long ts)
			throws SigarException {
			JSONObject diskInfo = new JSONObject();
			Map<String, JSONObject> diskMap = 
					new HashMap<String, JSONObject>();
			
			Map<String, Double> FSSumMap = new HashMap<String, Double>();
			FSSumMap.put("bytes_read:bytes/sec:AVG", 0.0);
			FSSumMap.put("reads: :AVG", 0.0);
			FSSumMap.put("bytes_write:bytes/sec:AVG", 0.0);
			FSSumMap.put("writes: :AVG", 0.0);
			FSSumMap.put("disk_total:GB:SUM", 0.0);
			FSSumMap.put("disk_used:GB:SUM", 0.0);
	
		    FileSystem[] fs = sigar.getFileSystemList();
		    for (int i = 0; i < fs.length; i++) {	      
		    	FileSystemUsage FSUsage = 
		    		  sigar.getFileSystemUsage(fs[i].getDirName());
		      
		    	double readBytes = FSUsage.getDiskReadBytes();
		    	double reads = FSUsage.getDiskReads();
		    	double writeBytes = FSUsage.getDiskWriteBytes();
		    	double writes = FSUsage.getDiskWrites();
		    	double diskTotal = FSUsage.getTotal() / 1024.0 / 1024.0;
		    	double diskUsed = FSUsage.getUsed() / 1024.0 / 1024.0;
		      
		    	Map<String, Double> FSStatusMap = 
		    		  previousDiskStats.get(fs[i].getDevName());
		    	if (FSStatusMap != null) {
		    		double deltaReadBytes = 
		    			   (readBytes - FSStatusMap.get("bytes_read")) 
		    			   / timerPeriod;
		    		double deltaReads = (reads - FSStatusMap.get("reads")) 
		    			  / timerPeriod;
		    		double deltaWriteBytes = 
		    			  (writeBytes - FSStatusMap.get("bytes_write"))
		    			  / timerPeriod;
		    		double deltaWrites = (writes - FSStatusMap.get("writes"))
		    			  / timerPeriod;
	    
		    		//sum the result
		    		FSSumMap.put("bytes_read:bytes/sec:AVG", 
		        		FSSumMap.get("bytes_read:bytes/sec:AVG") + deltaReadBytes);
		    		FSSumMap.put("reads: :AVG", 
		        		FSSumMap.get("reads: :AVG") + deltaReads);
		    		FSSumMap.put("bytes_write:bytes/sec:AVG", 
		        		FSSumMap.get("bytes_write:bytes/sec:AVG") + deltaWriteBytes);
		    		FSSumMap.put("writes: :AVG", 
		        		FSSumMap.get("writes: :AVG") + deltaWrites);
		    		FSSumMap.put("disk_total:GB:SUM", 
		    			FSSumMap.get("disk_total:GB:SUM") + diskTotal);
		    		FSSumMap.put("disk_used:GB:SUM", 
		    			FSSumMap.get("disk_used:GB:SUM") + diskUsed);
		    	} else {
		    		FSStatusMap = new HashMap<String, Double>();
		    		previousDiskStats.put(fs[i].getDevName(), FSStatusMap);
		    	}
		      
		    	FSStatusMap.put("bytes_read", readBytes);
		    	FSStatusMap.put("reads", reads);
		    	FSStatusMap.put("bytes_write", writeBytes);
		    	FSStatusMap.put("writes", writes);
		    }
		    
		    for (Map.Entry<String, Double> e : FSSumMap.entrySet()) {
		    	String[] param = e.getKey().split(":");
			    diskMap.put(param[0], 
			    		metricCombinerHelper((long)(e.getValue() * MULTIPLIER), 
			    				"double", param[1], "AVG", param[2], ts));
		    }
		    
		    diskInfo.putAll(diskMap);
		    storeObj.put("disk", diskInfo);
		}
	}
}