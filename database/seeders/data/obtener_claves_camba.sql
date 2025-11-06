create function obtener_camba_con_cucop(cucop_plus_entrada text)
returns text
language plpgsql
as $$
declare
	resultado text;
begin
	select b.camb into resultado
	from catalogo_cucop as a
	join catalogo_camb_cucop as b
		on a.cucop_plus = b.clave_cucop_plus
		where b.clave_cucop_plus = cucop_plus_entrada limit 1;
	return resultado;
end;
$$;