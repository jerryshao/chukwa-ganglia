package org.apache.hadoop.chukwa.contrib.chukwa_ganglia;

import java.util.Timer;

import org.apache.hadoop.chukwa.datacollection.adaptor.AbstractAdaptor;
import org.apache.hadoop.chukwa.datacollection.adaptor.AdaptorException;
import org.apache.hadoop.chukwa.datacollection.adaptor.AdaptorShutdownPolicy;
import org.apache.hadoop.chukwa.util.ExceptionUtil;
import org.apache.log4j.Logger;

public class GangliaSystemMetricsAdaptor extends AbstractAdaptor {
	private static Logger log = 
			Logger.getLogger(GangliaSystemMetricsAdaptor.class);
	
	private static int timerPeriod = 15000;
	private SystemMetricsRunner runner = null;
	private Timer timer = null;
	
	@Override
	public String parseArgs(String args) {
		try {
			timerPeriod = Integer.parseInt(args);
		} catch (NumberFormatException e) {
			log.warn(args + " cannot be parsed " + 
					ExceptionUtil.getStackTrace(e));
			return null;
		}
		
		timerPeriod *= 1000;
		return args;
	}
	
	@Override
	public void start(long offsest) throws AdaptorException {
		if (timer == null) {
			timer = new Timer();
			runner = new SystemMetricsRunner(dest, timerPeriod, 
					GangliaSystemMetricsAdaptor.this);
		}
		
		timer.scheduleAtFixedRate(runner, 0, timerPeriod);
	}
	
	@Override
	public String getCurrentStatus() {
		return type + " " + timerPeriod / 1000;
	}
	
	@Override
	public long shutdown(AdaptorShutdownPolicy shutdownPolicy) 
		throws AdaptorException {
		timer.cancel();
		return 0;
	}
}