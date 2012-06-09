package org.webodf;

import android.os.Bundle;

import com.phonegap.DroidGap;

public class WebODFActivity extends DroidGap {

	private String path;

	/** Called when the activity is first created. */
	@Override
	public void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);
		path = null;
		if (getIntent() != null && getIntent().getData() != null) {
			path = getIntent().getData().getPath();
		}
		setContentView(R.layout.main);
		super.loadUrl("file:///android_asset/www/index.html");
	}

	@Override
	protected void onResume() {
		super.onResume();
		if (path == null) {
			return;
		}
		String escapedPath = "file://" + path.replace("'", "\\'");
		sendJavascript("invokeString = '" + escapedPath + "';");
	}

}