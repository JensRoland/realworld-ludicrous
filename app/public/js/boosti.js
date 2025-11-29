/**
 * boosti.js - A fork of fixi.js with boost, confirm, and reset extensions
 * 
 * By Jens Roland (https://github.com/JensRoland/boosti)
 *
 * Based on fixi.js by Carson Gross (https://github.com/bigskysoftware/fixi)
 * Extended with:
 *   - fx-boost: SPA-like navigation for links and forms
 *   - fx-confirm: Confirmation dialogs before requests
 *   - fx-reset: Reset forms after successful submission
 *
 * Attributes:
 *   fx-action   - URL endpoint for the request (required for AJAX)
 *   fx-method   - HTTP method (default: GET)
 *   fx-target   - CSS selector for swap target (default: current element)
 *   fx-swap     - Swap mode: outerHTML, innerHTML, beforebegin, afterbegin, beforeend, afterend, none
 *   fx-trigger  - Event to trigger request (default: submit for forms, change for inputs, click otherwise)
 *   fx-confirm  - Show confirmation dialog with this message before request
 *   fx-reset    - Reset form after successful request
 *   fx-boost    - Set to "false" to disable boost for element and descendants
 *   fx-ignore   - Ignore fixi processing for element and descendants
 */
(()=>{
	if(document.__boosti) return;
	document.__boosti = true;

	// === FIXI CORE ===
	let defined = document.__fixi_mo;
	if (!defined) {
		document.__fixi_mo = new MutationObserver((recs)=>recs.forEach((r)=>r.type === "childList" && r.addedNodes.forEach((n)=>process(n))));
	}
	let send = (elt, type, detail, bub)=>elt.dispatchEvent(new CustomEvent("fx:" + type, {detail, cancelable:true, bubbles:bub !== false, composed:true}));
	let attr = (elt, name, defaultVal)=>elt.getAttribute(name) || defaultVal;
	let ignore = (elt)=>elt.closest("[fx-ignore]") != null;
	let init = (elt)=>{
		let options = {};
		if (elt.__fixi || ignore(elt) || !send(elt, "init", {options})) return;
		elt.__fixi = async(evt)=>{
			let reqs = elt.__fixi.requests ||= new Set();
			let form = elt.form || elt.closest("form");
			let body = new FormData(form ?? undefined, evt.submitter);
			if (!form && elt.name) body.append(elt.name, elt.value);
			let ac = new AbortController();
			let cfg = {
				trigger:evt,
				action:attr(elt, "fx-action"),
				method:attr(elt, "fx-method", "GET").toUpperCase(),
				target:document.querySelector(attr(elt, "fx-target")) ?? elt,
				swap:attr(elt, "fx-swap", "outerHTML"),
				body,
				drop:reqs.size,
				headers:{"FX-Request":"true"},
				abort:ac.abort.bind(ac),
				signal:ac.signal,
				preventTrigger:true,
				transition:document.startViewTransition?.bind(document),
				fetch:fetch.bind(window)
			};

			// === BOOSTI: fx-confirm ===
			let confirmMsg = elt.getAttribute("fx-confirm");
			if (confirmMsg) {
				cfg.confirm = () => Promise.resolve(confirm(confirmMsg));
			}

			let go = send(elt, "config", {cfg, requests:reqs});
			if (cfg.preventTrigger) evt.preventDefault();
			if (!go || cfg.drop) return;
			if (/GET|DELETE/.test(cfg.method)){
				let params = new URLSearchParams(cfg.body);
				if (params.size)
					cfg.action += (/\?/.test(cfg.action) ? "&" : "?") + params;
				cfg.body = null;
			}
			reqs.add(cfg);
			try {
				if (cfg.confirm){
					let result = await cfg.confirm();
					if (!result) return;
				}
				if (!send(elt, "before", {cfg, requests:reqs})) return;
				cfg.response = await cfg.fetch(cfg.action, cfg);
				cfg.text = await cfg.response.text();
				if (!send(elt, "after", {cfg})) return;
			} catch(error) {
				send(elt, "error", {cfg, error});
				return;
			} finally {
				reqs.delete(cfg);
				send(elt, "finally", {cfg});
			}
			let doSwap = ()=>{
				if (cfg.swap instanceof Function)
					return cfg.swap(cfg);
				else if (/(before|after)(begin|end)/.test(cfg.swap))
					cfg.target.insertAdjacentHTML(cfg.swap, cfg.text);
				else if(cfg.swap in cfg.target)
					cfg.target[cfg.swap] = cfg.text;
				else if(cfg.swap !== 'none') throw cfg.swap;
			};
			if (cfg.transition)
				await cfg.transition(doSwap).finished;
			else
				await doSwap();
			send(elt, "swapped", {cfg});
			if (!document.contains(elt)) send(document, "swapped", {cfg});

			// === BOOSTI: fx-reset ===
			if (elt.hasAttribute("fx-reset") && cfg.response.ok && form) {
				form.reset();
			}
		};
		elt.__fixi.evt = attr(elt, "fx-trigger", elt.matches("form") ? "submit" : elt.matches("input:not([type=button]),select,textarea") ? "change" : "click");
		elt.addEventListener(elt.__fixi.evt, elt.__fixi, options);
		send(elt, "inited", {}, false);
	};
	let process = (n)=>{
		if (n.matches){
			if (ignore(n)) return;
			if (n.matches("[fx-action]")) init(n);
		}
		if(n.querySelectorAll) n.querySelectorAll("[fx-action]").forEach(init);
	};
	document.addEventListener("fx:process", (evt)=>process(evt.target));

	// === BOOSTI: Boost (SPA-like navigation) ===
	async function boost(url, options) {
		try {
			const resp = await fetch(url, options);
			if (resp.redirected) {
				return boost(resp.url, { method: 'GET' });
			}
			const html = await resp.text();
			const doc = new DOMParser().parseFromString(html, 'text/html');
			const swap = () => {
				document.title = doc.title;
				document.body.innerHTML = doc.body.innerHTML;
				window.scrollTo(0, 0);
			};
			document.startViewTransition ? await document.startViewTransition(swap).finished : swap();
			if (!options || options.method === 'GET') {
				history.pushState({}, '', url);
			}
		} catch (e) {
			location.href = url;
		}
	}

	function isBoosted(el) {
		return !el.closest('[fx-boost="false"]');
	}

	// Boost links
	document.addEventListener('click', (e) => {
		const link = e.target.closest('a[href^="/"]');
		if (!link || link.hasAttribute('fx-action') || !isBoosted(link) || e.ctrlKey || e.metaKey || e.shiftKey) return;
		e.preventDefault();
		boost(link.href, { method: 'GET' });
	});

	// Boost forms
	document.addEventListener('submit', (e) => {
		const form = e.target;
		if (form.hasAttribute('fx-action') || !isBoosted(form)) return;
		e.preventDefault();
		const method = (form.method || 'GET').toUpperCase();
		const url = form.action || location.href;
		if (method === 'GET') {
			const params = new URLSearchParams(new FormData(form));
			boost(url + (url.includes('?') ? '&' : '?') + params, { method: 'GET' });
		} else {
			boost(url, { method, body: new FormData(form) });
		}
	});

	// Handle back/forward
	window.addEventListener('popstate', () => boost(location.href, { method: 'GET' }));

	// Initialize on DOMContentLoaded
	document.addEventListener("DOMContentLoaded", ()=>{
		document.__fixi_mo.observe(document.documentElement, {childList:true, subtree:true});
		process(document.body);
	});
})();
