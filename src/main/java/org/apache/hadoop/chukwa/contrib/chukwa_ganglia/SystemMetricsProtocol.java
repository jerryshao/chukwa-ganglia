package org.apache.hadoop.chukwa.contrib.chukwa_ganglia;

import org.hyperic.sigar.SigarException;
import org.json.simple.JSONObject;

public interface SystemMetricsProtocol {
	
	/**
	 * Get system information using Sigar package
	 * @param storeObj save information in storeObj JSON format
	 * @param ts current timestamp
	 * @throws SigarException
	 */
	public void getSystemInfo(JSONObject storeObj, long ts)
		throws SigarException;
}