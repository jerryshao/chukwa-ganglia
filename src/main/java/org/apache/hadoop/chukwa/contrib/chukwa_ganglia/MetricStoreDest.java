package org.apache.hadoop.chukwa.contrib.chukwa_ganglia;


public enum MetricStoreDest implements ChukwaGangliaConstants {
	HOST_HOUR(1, 3600, METRIC_DATA_TALBE),
	HOST_DAY(24, 3600 * 24, METRIC_DATA_DAY_TABLE), 
	HOST_WEEK(24*7, 3600 * 24 * 7, METRIC_DATA_WEEK_TABLE), 
	HOST_MONTH(24*30, 3600 * 24 * 30, METRIC_DATA_MONTH_TABLE), 
	HOST_YEAR(24*365, 3600 * 24 * 365, METRIC_DATA_YEAR_TABLE),
	
	CLUSTER_HOUR(1, 3600, CLUSTER_SUMMARY_TALBE),
	CLUSTER_DAY(24, 3600 * 24, CLUSTER_SUMMARY_DAY_TALBE), 
	CLUSTER_WEEK(24*7, 3600 * 24 * 7, CLUSTER_SUMMARY_WEEK_TALBE), 
	CLUSTER_MONTH(24*30, 3600 * 24 * 30, CLUSTER_SUMMARY_MONTH_TALBE), 
	CLUSTER_YEAR(24*365, 3600 * 24 * 365, CLUSTER_SUMMARY_YEAR_TALBE);	
	
	public final int consolidateNum;
	public final int second;
	public final String sourceString;
	
	private MetricStoreDest(int num, int sec, String s) { 
		consolidateNum = num;
		second = sec;
		sourceString = s;
	}
}