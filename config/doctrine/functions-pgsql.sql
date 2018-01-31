CREATE OR REPLACE FUNCTION sum_aggreg (p integer, n boolean) RETURNS integer AS $$
  SELECT $2::integer + $1 AS result;
$$ LANGUAGE sql IMMUTABLE;

DROP AGGREGATE IF EXISTS sum(boolean);

CREATE AGGREGATE sum (
  basetype = boolean,
  sfunc = sum_aggreg,
  stype = integer,
  initcond = 0
);

CREATE OR REPLACE FUNCTION manifestation_ends_at(happens_at timestamp, duration bigint) RETURNS timestamp AS $$
  SELECT $1::timestamp + ($2||' seconds')::interval AS result;
$$ LANGUAGE sql IMMUTABLE;
